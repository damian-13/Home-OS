<?php

namespace App\Expenses\Application\Dto;

use App\Expenses\Domain\Model\FinanceReviewRule;

final readonly class FinanceReviewRuleView
{
    public function __construct(
        public string $id,
        public string $targetType,
        public string $matchText,
        public ?string $categoryId,
        public ?string $incomeKind,
        public ?string $lastAppliedAt,
    ) {
    }

    public static function fromRule(FinanceReviewRule $rule): self
    {
        return new self(
            $rule->id(),
            $rule->targetType(),
            $rule->matchText(),
            $rule->categoryId(),
            $rule->incomeKind(),
            $rule->lastAppliedAt()?->format(DATE_ATOM),
        );
    }
}
