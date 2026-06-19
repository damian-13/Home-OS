<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\ExpenseCategory;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;

final readonly class CreateExpenseCategoryHandler implements CommandHandler
{
    public function __construct(
        private ExpenseRepository $expenses,
    ) {
    }

    public function handles(): string
    {
        return CreateExpenseCategoryCommand::class;
    }

    public function __invoke(CreateExpenseCategoryCommand $command): string
    {
        $category = new ExpenseCategory(
            (string) Uuid::new(),
            $command->householdId,
            trim($command->name),
            $this->slugify($command->name),
            trim($command->color) ?: '#2457ff',
        );

        $this->expenses->saveCategory($category);

        return $category->id();
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));

        return $slug !== '' ? $slug : 'category';
    }
}
