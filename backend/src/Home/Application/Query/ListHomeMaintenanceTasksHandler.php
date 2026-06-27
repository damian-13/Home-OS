<?php

namespace App\Home\Application\Query;

use App\Home\Application\Dto\HomeMaintenanceTaskView;
use App\Home\Domain\Repository\HomeMaintenanceRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class ListHomeMaintenanceTasksHandler implements QueryHandler
{
    public function __construct(
        private HomeMaintenanceRepository $tasks,
    ) {
    }

    public function handles(): string
    {
        return ListHomeMaintenanceTasksQuery::class;
    }

    /**
     * @return list<HomeMaintenanceTaskView>
     */
    public function __invoke(ListHomeMaintenanceTasksQuery $query): array
    {
        return array_map(
            static fn ($task): HomeMaintenanceTaskView => HomeMaintenanceTaskView::fromTask($task),
            $this->tasks->tasksForHousehold($query->householdId),
        );
    }
}
