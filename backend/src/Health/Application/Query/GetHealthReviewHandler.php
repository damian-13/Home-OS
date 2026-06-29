<?php

namespace App\Health\Application\Query;

use App\Health\Application\DefaultHealthMarkers;
use App\Health\Application\Dto\HealthReviewItemView;
use App\Health\Domain\Model\BloodTest;
use App\Health\Domain\Model\BloodTestMarker;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Query\QueryHandler;
use DateTimeImmutable;

final readonly class GetHealthReviewHandler implements QueryHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return GetHealthReviewQuery::class;
    }

    /**
     * @return array{items: list<HealthReviewItemView>, summary: array{total: int, critical: int, warning: int, info: int}, supportedTypes: list<string>, skippedTypes: array<string, string>}
     */
    public function __invoke(GetHealthReviewQuery $query): array
    {
        $bloodTests = $this->health->latestBloodTests($query->householdId, null, 100);
        $items = [
            ...$this->markerQualityItems($bloodTests),
            ...$this->duplicateCandidates($bloodTests),
            ...$this->staleMarkerItems($query->householdId, $this->health->markerNames($query->householdId)),
        ];

        usort($items, static function (HealthReviewItemView $left, HealthReviewItemView $right): int {
            $severityRank = ['critical' => 0, 'warning' => 1, 'info' => 2];

            return ($severityRank[$left->severity] ?? 9) <=> ($severityRank[$right->severity] ?? 9)
                ?: strcmp($right->detectedAt, $left->detectedAt);
        });

        return [
            'items' => array_slice($items, 0, 100),
            'summary' => [
                'total' => count($items),
                'critical' => count(array_filter($items, static fn (HealthReviewItemView $item): bool => $item->severity === 'critical')),
                'warning' => count(array_filter($items, static fn (HealthReviewItemView $item): bool => $item->severity === 'warning')),
                'info' => count(array_filter($items, static fn (HealthReviewItemView $item): bool => $item->severity === 'info')),
            ],
            'supportedTypes' => [
                'out_of_range_result',
                'missing_reference_range',
                'unknown_marker',
                'suspicious_unit',
                'duplicate_result_candidate',
                'stale_marker',
            ],
            'skippedTypes' => [
                'acknowledged_review_items' => 'Skipped because no safe persistent acknowledgement state exists yet.',
                'marker_mapping_update' => 'Skipped because marker mappings are currently a fixed catalog, not a persisted model.',
            ],
        ];
    }

    /**
     * @param list<BloodTest> $bloodTests
     * @return list<HealthReviewItemView>
     */
    private function markerQualityItems(array $bloodTests): array
    {
        $items = [];
        $catalog = $this->catalogByName();

        foreach ($bloodTests as $bloodTest) {
            foreach ($bloodTest->markers() as $marker) {
                if (in_array($marker->status(), ['low', 'high'], true)) {
                    $items[] = $this->markerItem(
                        'out_of_range_result',
                        'critical',
                        sprintf('%s is %s', $marker->name(), $marker->status()),
                        sprintf('Recorded value: %s %s on %s. This is based only on the saved status/reference data.', $this->number($marker->value()), $marker->unit(), $bloodTest->testedAt()->format('Y-m-d')),
                        $marker,
                        'Check the original lab result and edit the marker if the value, unit, range, or status is wrong.',
                    );
                }

                if ($marker->status() === 'unknown' || !isset($catalog[$this->normalize($marker->name())])) {
                    $items[] = $this->markerItem(
                        'unknown_marker',
                        'warning',
                        sprintf('Review marker mapping: %s', $marker->name()),
                        'The marker is unknown or its status is unknown, so trends and attention signals may be less reliable.',
                        $marker,
                        'Confirm marker name, unit, reference range, and status.',
                    );
                }

                if ($marker->referenceMin() === null && $marker->referenceMax() === null) {
                    $items[] = $this->markerItem(
                        'missing_reference_range',
                        'warning',
                        sprintf('Missing reference range: %s', $marker->name()),
                        'Both reference minimum and maximum are empty. Range-based review may not work for this marker.',
                        $marker,
                        'Add the reference range from the original lab result if it is available.',
                    );
                } elseif ($marker->referenceMin() !== null && $marker->referenceMax() !== null && $marker->referenceMin() > $marker->referenceMax()) {
                    $items[] = $this->markerItem(
                        'missing_reference_range',
                        'warning',
                        sprintf('Suspicious reference range: %s', $marker->name()),
                        sprintf('Reference minimum %s is higher than maximum %s.', $this->number($marker->referenceMin()), $this->number($marker->referenceMax())),
                        $marker,
                        'Check and correct the reference range.',
                    );
                }

                $expectedUnit = $catalog[$this->normalize($marker->name())]['unit'] ?? null;
                if (trim($marker->unit()) === '' || ($expectedUnit && $this->normalize($marker->unit()) !== $this->normalize($expectedUnit))) {
                    $items[] = $this->markerItem(
                        'suspicious_unit',
                        'warning',
                        sprintf('Check unit: %s', $marker->name()),
                        $expectedUnit
                            ? sprintf('Saved unit is "%s"; catalog unit is "%s".', $marker->unit(), $expectedUnit)
                            : 'Unit is missing or could not be compared with the catalog.',
                        $marker,
                        'Confirm the unit against the original lab result.',
                    );
                }
            }
        }

        return $this->deduplicateItems($items);
    }

    /**
     * @param list<BloodTest> $bloodTests
     * @return list<HealthReviewItemView>
     */
    private function duplicateCandidates(array $bloodTests): array
    {
        $groups = [];

        foreach ($bloodTests as $bloodTest) {
            $markerNames = array_map(static fn (BloodTestMarker $marker): string => $marker->name(), $bloodTest->markers());
            sort($markerNames);
            $key = implode('|', [$bloodTest->memberId(), $bloodTest->testedAt()->format('Y-m-d'), $bloodTest->labName() ?? '', implode(',', $markerNames)]);
            $groups[$key][] = $bloodTest;
        }

        $items = [];

        foreach ($groups as $tests) {
            if (count($tests) < 2) {
                continue;
            }

            $first = $tests[0];
            $items[] = new HealthReviewItemView(
                sprintf('duplicate-result-%s', $first->id()),
                'duplicate_result_candidate',
                'info',
                sprintf('Possible duplicate lab result: %s', $first->testedAt()->format('Y-m-d')),
                sprintf('%d similar lab results exist for the same member/date/lab and marker set.', count($tests)),
                $first->memberId(),
                null,
                $first->id(),
                $first->id(),
                $first->testedAt()->format('Y-m-d'),
                '#health-review',
                'Open Health and compare the duplicate-looking results before deleting anything.',
            );
        }

        return $items;
    }

    /**
     * @param list<string> $markerNames
     * @return list<HealthReviewItemView>
     */
    private function staleMarkerItems(string $householdId, array $markerNames): array
    {
        $items = [];
        $cutoff = new DateTimeImmutable('-12 months');

        foreach ($markerNames as $markerName) {
            $latest = $this->health->markerHistory($householdId, $markerName, null, 1)[0] ?? null;

            if (!$latest instanceof BloodTestMarker || $latest->bloodTest()->testedAt() >= $cutoff) {
                continue;
            }

            $items[] = new HealthReviewItemView(
                sprintf('stale-marker-%s', md5($markerName)),
                'stale_marker',
                'info',
                sprintf('Marker not updated for over 12 months: %s', $markerName),
                sprintf('Latest saved result is from %s.', $latest->bloodTest()->testedAt()->format('Y-m-d')),
                $latest->bloodTest()->memberId(),
                $latest->id(),
                $latest->bloodTest()->id(),
                $latest->bloodTest()->id(),
                $latest->bloodTest()->testedAt()->format('Y-m-d'),
                '#health-review',
                'Use this as an organizational reminder only; decide with a clinician whether another test is needed.',
            );
        }

        return $items;
    }

    private function markerItem(string $type, string $severity, string $title, string $detail, BloodTestMarker $marker, string $action): HealthReviewItemView
    {
        return new HealthReviewItemView(
            sprintf('%s-%s', $type, $marker->id()),
            $type,
            $severity,
            $title,
            $detail,
            $marker->bloodTest()->memberId(),
            $marker->id(),
            $marker->bloodTest()->id(),
            $marker->bloodTest()->id(),
            $marker->bloodTest()->testedAt()->format('Y-m-d'),
            '#health-review',
            $action,
        );
    }

    /**
     * @param list<HealthReviewItemView> $items
     * @return list<HealthReviewItemView>
     */
    private function deduplicateItems(array $items): array
    {
        $seen = [];
        $unique = [];

        foreach ($items as $item) {
            if (isset($seen[$item->id])) {
                continue;
            }

            $seen[$item->id] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    /**
     * @return array<string, array{name: string, aliases: list<string>, unit: string, referenceMin: float|null, referenceMax: float|null, category: string}>
     */
    private function catalogByName(): array
    {
        $catalog = [];

        foreach (DefaultHealthMarkers::all() as $marker) {
            $catalog[$this->normalize($marker['name'])] = $marker;

            foreach ($marker['aliases'] as $alias) {
                $catalog[$this->normalize($alias)] = $marker;
            }
        }

        return $catalog;
    }

    private function normalize(string $value): string
    {
        $value = str_replace(['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'], ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], strtolower($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? $value;
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(sprintf('%.3F', $value), '0'), '.');
    }
}
