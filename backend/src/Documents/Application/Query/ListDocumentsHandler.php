<?php

namespace App\Documents\Application\Query;

use App\Documents\Application\Dto\DocumentView;
use App\Documents\Domain\Repository\DocumentRepository;
use App\Shared\Application\Query\QueryHandler;

final readonly class ListDocumentsHandler implements QueryHandler
{
    public function __construct(
        private DocumentRepository $documents,
    ) {
    }

    public function handles(): string
    {
        return ListDocumentsQuery::class;
    }

    /**
     * @return list<DocumentView>
     */
    public function __invoke(ListDocumentsQuery $query): array
    {
        return array_map(
            static fn ($document): DocumentView => DocumentView::fromDocument($document),
            $this->documents->documentsForHousehold($query->householdId),
        );
    }
}
