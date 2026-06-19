<?php

namespace App\Shared\Application\Command;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class SynchronousCommandBus implements CommandBus
{
    /** @var array<class-string<Command>, CommandHandler> */
    private array $handlers = [];

    /**
     * @param iterable<CommandHandler> $handlers
     */
    public function __construct(
        #[TaggedIterator('app.command_handler')]
        iterable $handlers,
    ) {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->handles()] = $handler;
        }
    }

    public function dispatch(Command $command): mixed
    {
        $commandClass = $command::class;
        $handler = $this->handlers[$commandClass] ?? null;

        if (!$handler) {
            throw new RuntimeException(sprintf('No command handler registered for "%s".', $commandClass));
        }

        return $handler($command);
    }
}
