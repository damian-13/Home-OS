<?php

namespace App\Identity\Application\Dto;

use App\Identity\Domain\Model\UserAccount;

final readonly class CurrentUserView
{
    public function __construct(
        public string $id,
        public string $email,
        public string $displayName,
        public string $householdId,
        public ?string $linkedMemberId,
    ) {
    }

    public static function fromUser(UserAccount $user): self
    {
        return new self(
            $user->id(),
            $user->email(),
            $user->displayName(),
            $user->householdId(),
            $user->linkedMemberId(),
        );
    }
}
