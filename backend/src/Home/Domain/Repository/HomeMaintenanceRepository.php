<?php

namespace App\Home\Domain\Repository;

use App\Home\Domain\Model\HomeMaintenanceTask;
use DateTimeImmutable;

interface HomeMaintenanceRepository
{
    public function save(HomeMaintenanceTask $task): void;

    public function get(string $householdId, string $taskId): HomeMaintenanceTask;

    /**
     * @return list<HomeMaintenanceTask>
     */
    public function tasksForHousehold(string $householdId): array;

    /**
     * @return list<HomeMaintenanceTask>
     */
    public function overdueTasks(string $householdId, DateTimeImmutable $today, int $limit = 10): array;

    /**
     * @return list<HomeMaintenanceTask>
     */
    public function upcomingTasks(string $householdId, DateTimeImmutable $today, int $days = 14, int $limit = 10): array;
}
