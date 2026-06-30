<?php

namespace App\Notifications\Application\Dto;

final readonly class NotificationDigestSectionView
{
    /**
     * @param list<NotificationDigestItemView> $items
     */
    public function __construct(
        public string $key,
        public string $title,
        public int $count,
        public array $items,
    ) {
    }
}
