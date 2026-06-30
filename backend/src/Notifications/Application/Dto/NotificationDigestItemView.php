<?php

namespace App\Notifications\Application\Dto;

final readonly class NotificationDigestItemView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $detail,
        public string $severity,
        public string $targetUrl,
        public ?string $dueAt = null,
    ) {
    }
}
