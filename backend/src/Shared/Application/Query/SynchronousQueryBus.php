<?php

namespace App\Shared\Application\Query;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class SynchronousQueryBus implements QueryBus
{
    /** @var array<class-string<Query>, QueryHandler> */
    private array $handlers = [];

    /**
     * @param iterable<QueryHandler> $handlers
     */
    public function __construct(
        #[TaggedIterator('app.query_handler')]
        iterable $handlers,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->handles()] = $handler;
        }
    }

    public function ask(Query $query): mixed
    {
        $queryClass = $query::class;
        $handler = $this->handlers[$queryClass] ?? null;

        if (!$handler) {
            throw new RuntimeException(sprintf('No query handler registered for "%s".', $queryClass));
        }

        return $handler($query);
    }
}
