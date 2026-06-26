<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\FinanceReviewBatch;

final readonly class FinanceReviewBatchView
{
    public function __construct(
        public string $id,
        public string $targetType,
        public string $matchText,
        public int $appliedCount,
        public string $createdAt,
    ) {
    }

    public static function fromBatch(FinanceReviewBatch $batch): self
    {
        return new self(
            $batch->id(),
            $batch->targetType(),
            $batch->matchText(),
            $batch->appliedCount(),
            $batch->createdAt()->format(DATE_ATOM),
        );
    }
}
