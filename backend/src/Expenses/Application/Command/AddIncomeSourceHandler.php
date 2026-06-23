<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\IncomeSource;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use InvalidArgumentException;

final readonly class AddIncomeSourceHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return AddIncomeSourceCommand::class; }
    public function __invoke(AddIncomeSourceCommand $command): string
    {
        if ($command->amount <= 0 || trim($command->name) === '') {
            throw new InvalidArgumentException('Income source name and amount are required.');
        }
        $source = new IncomeSource((string) Uuid::new(), $command->householdId, $command->memberId, trim($command->name), (int) round($command->amount * 100), 'PLN');
        $this->expenses->saveIncomeSource($source);
        return $source->id();
    }
}
