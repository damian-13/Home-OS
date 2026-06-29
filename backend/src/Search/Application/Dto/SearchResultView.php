<?php

namespace App\Search\Application\Dto;

final readonly class SearchResultView
{
    public function __construct(
        public string $id,
        public string $sourceModule,
        public string $sourceType,
        public string $sourceId,
        public string $title,
        public string $detail,
        public ?string $date,
        public string $targetUrl,
        public int $relevance,
    ) {
    }
}
