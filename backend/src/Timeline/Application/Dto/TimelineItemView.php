<?php

namespace App\Timeline\Application\Dto;

final readonly class TimelineItemView
{
    public function __construct(
        public string $id,
        public string $sourceModule,
        public string $sourceType,
        public string $sourceId,
        public string $eventType,
        public string $title,
        public string $detail,
        public string $occurredAt,
        public string $targetUrl,
        public string $importance,
    ) {
    }
}
