<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardView
{
    /**
     * @param array{homeTasksDue: int, remindersDue: int, inboxItemsDue: int, inboxHighestSeverity: ?string, monthlySpend: float, projectedBalance: float, financeReviewCount: int, healthMarkersTracked: int, healthOutOfRange: int, healthReviewCount: int, healthReviewCritical: int, documentsStored: int} $summary
     * @param list<DashboardAttentionItemView> $attention
     */
    public function __construct(
        public string $app,
        public string $status,
        public array $summary,
        public array $attention,
    ) {
    }
}
