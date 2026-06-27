<?php

namespace App\Reminders\Application\Query;

use App\Reminders\Application\Dto\ReminderView;
use App\Reminders\Domain\Repository\ReminderRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class ListRemindersHandler implements QueryHandler
{
    public function __construct(
        private ReminderRepository $reminders,
    ) {
    }

    public function handles(): string
    {
        return ListRemindersQuery::class;
    }

    /**
     * @return list<ReminderView>
     */
    public function __invoke(ListRemindersQuery $query): array
    {
        return array_map(
            static fn ($reminder): ReminderView => ReminderView::fromReminder($reminder),
            $this->reminders->remindersForHousehold($query->householdId),
        );
    }
}
