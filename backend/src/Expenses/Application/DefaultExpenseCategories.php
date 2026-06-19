<?php

namespace App\Expenses\Application;

final readonly class DefaultExpenseCategories
{
    /**
     * @return list<array{name: string, slug: string, color: string}>
     */
    public static function all(): array
    {
        return [
            ['name' => 'Bills', 'slug' => 'bills', 'color' => '#2457ff'],
            ['name' => 'Groceries/Home', 'slug' => 'groceries-home', 'color' => '#18a67a'],
            ['name' => 'Mortgage', 'slug' => 'mortgage', 'color' => '#f97316'],
            ['name' => 'Phone/Internet', 'slug' => 'phone-internet', 'color' => '#7c3aed'],
            ['name' => 'Health', 'slug' => 'health', 'color' => '#e11d48'],
            ['name' => 'Transport', 'slug' => 'transport', 'color' => '#0f766e'],
            ['name' => 'Other', 'slug' => 'other', 'color' => '#64748b'],
        ];
    }
}
