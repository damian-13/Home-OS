<?php

namespace App\Inbox\Application\Dto;

final readonly class InboxItemView
{
    public function __construct(
        public string $id,
        public string $sourceModule,
        public string $sourceType,
        public string $sourceId,
        public string $severity,
        public ?float $confidence,
        public string $title,
        public string $detail,
        public string $targetAction,
        public string $targetUrl,
        public string $targetPage,
        public ?string $targetSection,
        public string $detectedAt,
        public ?string $dueAt,
        public ?string $status,
    ) {
    }
}
