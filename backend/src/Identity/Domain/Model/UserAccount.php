<?php

namespace App\Identity\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_accounts')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_ACCOUNTS_EMAIL', columns: ['email'])]
class UserAccount implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $passwordHash;

    #[ORM\Column(type: 'string', length: 120)]
    private string $displayName;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $linkedMemberId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $notificationDigestEnabled = true;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $notificationDigestHour = null;

    #[ORM\Column(type: 'string', length: 2)]
    private string $language = 'en';

    public function __construct(string $id, string $email, string $passwordHash, string $displayName, string $householdId, ?string $linkedMemberId)
    {
        $this->id = $id;
        $this->email = strtolower($email);
        $this->passwordHash = $passwordHash;
        $this->displayName = $displayName;
        $this->householdId = $householdId;
        $this->linkedMemberId = $linkedMemberId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function linkedMemberId(): ?string
    {
        return $this->linkedMemberId;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function notificationDigestEnabled(): bool
    {
        return $this->notificationDigestEnabled;
    }

    public function notificationDigestHour(): ?int
    {
        return $this->notificationDigestHour;
    }

    public function language(): string
    {
        return $this->language;
    }

    public function changeLanguage(string $language): void
    {
        if (!in_array($language, ['en', 'pl'], true)) {
            throw new \InvalidArgumentException('Unsupported language.');
        }

        $this->language = $language;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function eraseCredentials(): void
    {
    }
}
