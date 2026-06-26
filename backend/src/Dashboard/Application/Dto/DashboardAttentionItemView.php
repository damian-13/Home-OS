<?php

namespace App\Dashboard\Application\Dto;

final readonly class DashboardAttentionItemView
{
    public function __construct(
        public string $id,
        public string $area,
        public string $severity,
        public string $title,
        public string $detail,
        public string $actionLabel,
        public string $targetPage,
        public ?string $targetSection = null,
    ) {
    }
}
