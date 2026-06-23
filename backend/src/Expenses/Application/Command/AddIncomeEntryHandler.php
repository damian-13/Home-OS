<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Model\IncomeEntry;
use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Uuid;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class AddIncomeEntryHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return AddIncomeEntryCommand::class; }
    public function __invoke(AddIncomeEntryCommand $command): string
    {
        if ($command->amount <= 0 || trim($command->description) === '') {
            throw new InvalidArgumentException('Income description and amount are required.');
        }
        $entry = new IncomeEntry((string) Uuid::new(), $command->householdId, $command->sourceId, $command->memberId, trim($command->description), (int) round($command->amount * 100), 'PLN', new DateTimeImmutable($command->receivedOn));
        $this->expenses->saveIncomeEntry($entry);
        return $entry->id();
    }
}
