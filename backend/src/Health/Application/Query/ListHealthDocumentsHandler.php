<?php

namespace App\Health\Application\Query;

use App\Health\Application\Dto\HealthDocumentView;
use App\Health\Domain\Repository\HealthRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class ListHealthDocumentsHandler implements QueryHandler
{
    public function __construct(
        private HealthRepository $health,
    ) {
    }

    public function handles(): string
    {
        return ListHealthDocumentsQuery::class;
    }

    /**
     * @return list<HealthDocumentView>
     */
    public function __invoke(ListHealthDocumentsQuery $query): array
    {
        return array_map(
            static fn ($document): HealthDocumentView => HealthDocumentView::fromDocument($document),
            $this->health->latestDocuments($query->householdId, $query->memberId),
        );
    }
}
