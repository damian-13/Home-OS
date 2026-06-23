<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use InvalidArgumentException;

final readonly class UpdateIncomeSourceHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return UpdateIncomeSourceCommand::class; }
    public function __invoke(UpdateIncomeSourceCommand $command): string
    {
        if ($command->amount <= 0 || trim($command->name) === '') {
            throw new InvalidArgumentException('Income source name and amount are required.');
        }
        $source = $this->expenses->getIncomeSource($command->householdId, $command->sourceId);
        $source->changeDetails($command->memberId, trim($command->name), (int) round($command->amount * 100), $command->active);
        $this->expenses->saveIncomeSource($source);
        return $source->id();
    }
}
