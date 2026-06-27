<?php

namespace App\Inbox\Application\Dto;

final readonly class InboxView
{
    /**
     * @param list<InboxItemView> $items
     * @param array{total: int, critical: int, warning: int, info: int, highestSeverity: ?string} $summary
     */
    public function __construct(
        public array $items,
        public array $summary,
    ) {
    }
}
