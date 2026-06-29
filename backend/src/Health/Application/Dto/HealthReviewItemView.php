<?php

namespace App\Health\Application\Dto;

final readonly class HealthReviewItemView
{
    public function __construct(
        public string $id,
        public string $type,
        public string $severity,
        public string $title,
        public string $detail,
        public ?string $memberId,
        public ?string $markerId,
        public ?string $labTestId,
        public ?string $resultId,
        public string $detectedAt,
        public string $targetUrl,
        public string $suggestedAction,
    ) {
    }
}
