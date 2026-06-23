<?php

namespace App\Health\Application\Command;

use App\Health\Application\DefaultHealthMarkers;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UpdateBloodTestHandler implements CommandHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return UpdateBloodTestCommand::class;
    }

    public function __invoke(UpdateBloodTestCommand $command): string
    {
        if ($command->markers === []) {
            throw new InvalidArgumentException('Blood test must contain at least one marker.');
        }

        $bloodTest = $this->health->bloodTestById($command->householdId, $command->bloodTestId);

        if (!$bloodTest) {
            throw new InvalidArgumentException('Blood test not found.');
        }

        $markers = [];

        foreach ($command->markers as $marker) {
            $name = trim((string) ($marker['markerName'] ?? $marker['name'] ?? ''));
            $unit = trim((string) ($marker['unit'] ?? ''));

            if ($name === '' || $unit === '') {
                throw new InvalidArgumentException('Marker name and unit are required.');
            }

            $markers[] = [
                'id' => (string) Uuid::new(),
                'name' => DefaultHealthMarkers::canonicalName($name),
                'value' => (float) ($marker['value'] ?? 0),
                'unit' => $unit,
                'referenceMin' => $this->nullableFloat($marker['referenceMin'] ?? null),
                'referenceMax' => $this->nullableFloat($marker['referenceMax'] ?? null),
                'status' => $this->normalizeStatus($marker['status'] ?? null),
                'notes' => isset($marker['notes']) && trim((string) $marker['notes']) !== '' ? trim((string) $marker['notes']) : null,
            ];
        }

        $bloodTest->replaceDetails(
            $command->memberId,
            new DateTimeImmutable($command->testedAt),
            $command->labName ? trim($command->labName) : null,
            $command->notes ? trim($command->notes) : null,
            $markers,
        );

        $this->health->saveBloodTest($bloodTest);

        return $bloodTest->id();
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = (string) $status;

        if (in_array($status, ['normal', 'low', 'high'], true)) {
            return $status;
        }

        return 'unknown';
    }
}
