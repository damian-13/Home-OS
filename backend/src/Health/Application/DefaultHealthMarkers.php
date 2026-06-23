<?php

namespace App\Health\Application;

final readonly class DefaultHealthMarkers
{
    /**
     * @return list<array{name: string, aliases: list<string>, unit: string, referenceMin: float|null, referenceMax: float|null, category: string}>
     */
    public static function all(): array
    {
        return [
            ['name' => 'Hemoglobina', 'aliases' => ['HGB'], 'unit' => 'g/dl', 'referenceMin' => 12.0, 'referenceMax' => 17.5, 'category' => 'Morfologia'],
            ['name' => 'Leukocyty', 'aliases' => ['WBC'], 'unit' => 'tys/ul', 'referenceMin' => 4.0, 'referenceMax' => 10.0, 'category' => 'Morfologia'],
            ['name' => 'Erytrocyty', 'aliases' => ['RBC'], 'unit' => 'mln/ul', 'referenceMin' => 4.0, 'referenceMax' => 5.8, 'category' => 'Morfologia'],
            ['name' => 'Hematokryt', 'aliases' => ['HCT'], 'unit' => '%', 'referenceMin' => 36.0, 'referenceMax' => 50.0, 'category' => 'Morfologia'],
            ['name' => 'Płytki krwi', 'aliases' => ['PLT'], 'unit' => 'tys/ul', 'referenceMin' => 150.0, 'referenceMax' => 400.0, 'category' => 'Morfologia'],
            ['name' => 'MCV', 'aliases' => [], 'unit' => 'fl', 'referenceMin' => 80.0, 'referenceMax' => 100.0, 'category' => 'Morfologia'],
            ['name' => 'MCH', 'aliases' => [], 'unit' => 'pg', 'referenceMin' => 27.0, 'referenceMax' => 33.0, 'category' => 'Morfologia'],
            ['name' => 'MCHC', 'aliases' => [], 'unit' => 'g/dl', 'referenceMin' => 32.0, 'referenceMax' => 36.0, 'category' => 'Morfologia'],
            ['name' => 'TSH', 'aliases' => [], 'unit' => 'mIU/L', 'referenceMin' => 0.27, 'referenceMax' => 4.2, 'category' => 'Tarczyca'],
            ['name' => 'FT3', 'aliases' => [], 'unit' => 'pmol/L', 'referenceMin' => null, 'referenceMax' => null, 'category' => 'Tarczyca'],
            ['name' => 'FT4', 'aliases' => [], 'unit' => 'pmol/L', 'referenceMin' => null, 'referenceMax' => null, 'category' => 'Tarczyca'],
            ['name' => 'Glukoza', 'aliases' => [], 'unit' => 'mg/dl', 'referenceMin' => 70.0, 'referenceMax' => 99.0, 'category' => 'Metabolizm'],
            ['name' => 'Cholesterol całkowity', 'aliases' => [], 'unit' => 'mg/dl', 'referenceMin' => null, 'referenceMax' => 190.0, 'category' => 'Lipidy'],
            ['name' => 'HDL', 'aliases' => [], 'unit' => 'mg/dl', 'referenceMin' => 40.0, 'referenceMax' => null, 'category' => 'Lipidy'],
            ['name' => 'LDL', 'aliases' => [], 'unit' => 'mg/dl', 'referenceMin' => null, 'referenceMax' => 115.0, 'category' => 'Lipidy'],
            ['name' => 'Trójglicerydy', 'aliases' => ['TG'], 'unit' => 'mg/dl', 'referenceMin' => null, 'referenceMax' => 150.0, 'category' => 'Lipidy'],
            ['name' => 'CRP', 'aliases' => [], 'unit' => 'mg/l', 'referenceMin' => null, 'referenceMax' => 5.0, 'category' => 'Stan zapalny'],
            ['name' => 'Ferrytyna', 'aliases' => [], 'unit' => 'ng/ml', 'referenceMin' => null, 'referenceMax' => null, 'category' => 'Żelazo'],
            ['name' => 'Witamina D', 'aliases' => ['25(OH)D'], 'unit' => 'ng/ml', 'referenceMin' => 30.0, 'referenceMax' => 100.0, 'category' => 'Witaminy'],
            ['name' => 'ALT', 'aliases' => [], 'unit' => 'U/l', 'referenceMin' => null, 'referenceMax' => 41.0, 'category' => 'Wątroba'],
            ['name' => 'AST', 'aliases' => [], 'unit' => 'U/l', 'referenceMin' => null, 'referenceMax' => 40.0, 'category' => 'Wątroba'],
            ['name' => 'Kreatynina', 'aliases' => [], 'unit' => 'mg/dl', 'referenceMin' => null, 'referenceMax' => null, 'category' => 'Nerki'],
        ];
    }

    public static function canonicalName(string $name): string
    {
        $normalized = self::normalize($name);

        foreach (self::all() as $marker) {
            if (self::normalize($marker['name']) === $normalized) {
                return $marker['name'];
            }

            foreach ($marker['aliases'] as $alias) {
                if (self::normalize($alias) === $normalized) {
                    return $marker['name'];
                }
            }
        }

        return trim($name);
    }

    private static function normalize(string $value): string
    {
        $value = str_replace(['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'], ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'], mb_strtolower($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? $value;
    }
}
