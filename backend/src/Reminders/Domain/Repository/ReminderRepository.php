<?php

namespace App\Reminders\Domain\Repository;

use App\Reminders\Domain\Model\Reminder;
use DateTimeImmutable;

interface ReminderRepository
{
    public function save(Reminder $reminder): void;

    public function get(string $householdId, string $reminderId): Reminder;

    /**
     * @return list<Reminder>
     */
    public function remindersForHousehold(string $householdId): array;

    /**
     * @return list<Reminder>
     */
    public function overdueReminders(string $householdId, DateTimeImmutable $today, int $limit = 10): array;

    /**
     * @return list<Reminder>
     */
    public function dueTodayReminders(string $householdId, DateTimeImmutable $today, int $limit = 10): array;

    /**
     * @return list<Reminder>
     */
    public function upcomingReminders(string $householdId, DateTimeImmutable $today, int $days = 14, int $limit = 10): array;
}
