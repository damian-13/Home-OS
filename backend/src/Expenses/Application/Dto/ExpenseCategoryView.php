<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\ExpenseCategory;

final readonly class ExpenseCategoryView
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $color,
    ) {
    }

    public static function fromCategory(ExpenseCategory $category): self
    {
        return new self($category->id(), $category->name(), $category->slug(), $category->color());
    }
}
