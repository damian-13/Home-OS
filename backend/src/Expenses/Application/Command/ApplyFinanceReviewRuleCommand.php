<?php

namespace App\Expenses\Application\Command;

use App\Shared\Application\Command\Command;

final readonly class ApplyFinanceReviewRuleCommand implements Command
{
    public function __construct(
        public string $householdId,
        public string $targetType,
        public string $matchText,
        public string $month,
        public ?string $categoryId,
        public ?string $incomeKind,
    ) {
    }
}
