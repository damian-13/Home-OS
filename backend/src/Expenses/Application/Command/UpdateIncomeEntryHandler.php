<?php

namespace App\Expenses\Application\Command;

use App\Expenses\Domain\Repository\ExpenseRepository;
use App\Shared\Application\Command\CommandHandler;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class UpdateIncomeEntryHandler implements CommandHandler
{
    public function __construct(private ExpenseRepository $expenses) {}
    public function handles(): string { return UpdateIncomeEntryCommand::class; }
    public function __invoke(UpdateIncomeEntryCommand $command): string
    {
        if ($command->amount <= 0 || trim($command->description) === '') {
            throw new InvalidArgumentException('Income description and amount are required.');
        }
        $entry = $this->expenses->getIncomeEntry($command->householdId, $command->entryId);
        $entry->changeDetails($command->sourceId, $command->memberId, trim($command->description), (int) round($command->amount * 100), new DateTimeImmutable($command->receivedOn));
        $this->expenses->saveIncomeEntry($entry);
        return $entry->id();
    }
}
