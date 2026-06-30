<?php

namespace App\Notifications\Application\Dto;

final readonly class NotificationDigestView
{
    /**
     * @param list<NotificationDigestSectionView> $sections
     * @param array<string, int> $counts
     */
    public function __construct(
        public string $householdId,
        public string $generatedAt,
        public array $sections,
        public array $counts,
        public int $totalItems,
    ) {
    }
}
