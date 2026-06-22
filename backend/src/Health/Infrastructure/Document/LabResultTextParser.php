<?php

namespace App\Health\Infrastructure\Document;

final readonly class LabResultTextParser
{
    private const MARKERS = [
        'Leukocyty' => ['unit' => 'tys/ul', 'scaleIfInteger' => 10],
        'Erytrocyty' => ['unit' => 'mln/ul', 'scaleIfInteger' => 10],
        'Hemoglobina' => ['unit' => 'g/dl', 'scaleIfInteger' => 10],
        'Hematokryt' => ['unit' => '%', 'scaleIfInteger' => null],
        'MCV' => ['unit' => 'fl', 'scaleIfInteger' => null],
        'MCH' => ['unit' => 'pg', 'scaleIfInteger' => null],
        'MCHC' => ['unit' => 'g/dl', 'scaleIfInteger' => 10],
        'Płytki krwi' => ['unit' => 'tys/ul', 'scaleIfInteger' => null],
        'Limfocyty %' => ['unit' => '%', 'scaleIfInteger' => null],
        'Inne(Eo,Bazo,Mono) %' => ['unit' => '%', 'scaleIfInteger' => null],
        'Neutrofile %' => ['unit' => '%', 'scaleIfInteger' => null],
        'Limfocyty #' => ['unit' => 'tys/ul', 'scaleIfInteger' => 10],
        'Inne(Eo,Bazo,Mono) #' => ['unit' => 'tys/ul', 'scaleIfInteger' => 10],
        'Neutrofile #' => ['unit' => 'tys/ul', 'scaleIfInteger' => 100],
        'RDW-CV' => ['unit' => '%', 'scaleIfInteger' => null],
        'PDW' => ['unit' => '', 'scaleIfInteger' => null],
        'MPV' => ['unit' => 'fl', 'scaleIfInteger' => 100],
        'P-LCR' => ['unit' => '', 'scaleIfInteger' => null],
    ];

    /**
     * @return list<array{markerName: string, value: float, unit: string, referenceMin: float|null, referenceMax: float|null, status: string, notes: string|null}>
     */
    public function parse(string $text): array
    {
        $markers = [];
        $normalizedText = str_replace(["\r\n", "\r"], "\n", $text);

        $markerDefinitions = self::MARKERS;
        uksort($markerDefinitions, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach (explode("\n", $normalizedText) as $line) {
            $line = trim(preg_replace('/\s+/', ' ', $line) ?? $line);

            if ($line === '') {
                continue;
            }

            foreach ($markerDefinitions as $name => $metadata) {
                if (!str_starts_with($this->normalizeForMatch($line), $this->normalizeForMatch($name))) {
                    continue;
                }

                $numbers = $this->numbersFromLine(substr($line, strlen($name)));

                if ($numbers === []) {
                    continue;
                }

                $scale = $metadata['scaleIfInteger'];
                $value = $this->normalizeNumber($numbers[0], $scale);
                $referenceMin = isset($numbers[1]) ? $this->normalizeNumber($numbers[1], $scale) : null;
                $referenceMax = isset($numbers[2]) ? $this->normalizeNumber($numbers[2], $scale) : null;

                $markers[$name] = [
                    'markerName' => $name,
                    'value' => $value,
                    'unit' => $metadata['unit'],
                    'referenceMin' => $referenceMin,
                    'referenceMax' => $referenceMax,
                    'status' => $this->status($value, $referenceMin, $referenceMax),
                    'notes' => 'Suggested from OCR. Review before saving.',
                ];

                break;
            }
        }

        return array_values($markers);
    }

    private function normalizeForMatch(string $value): string
    {
        $value = str_replace(['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'], ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], mb_strtolower($value));

        return preg_replace('/[^a-z0-9#%()-]+/', '', $value) ?? $value;
    }

    /**
     * @return list<string>
     */
    private function numbersFromLine(string $line): array
    {
        preg_match_all('/\d+(?:[,.]\d+)?/', $line, $matches);

        return $matches[0];
    }

    private function normalizeNumber(string $value, ?int $scaleIfInteger): float
    {
        $normalized = str_replace(',', '.', $value);

        if ($scaleIfInteger && !str_contains($normalized, '.')) {
            return ((float) $normalized) / $scaleIfInteger;
        }

        return (float) $normalized;
    }

    private function status(float $value, ?float $referenceMin, ?float $referenceMax): string
    {
        if ($referenceMin !== null && $value < $referenceMin) {
            return 'low';
        }

        if ($referenceMax !== null && $value > $referenceMax) {
            return 'high';
        }

        if ($referenceMin !== null || $referenceMax !== null) {
            return 'normal';
        }

        return 'unknown';
    }
}
