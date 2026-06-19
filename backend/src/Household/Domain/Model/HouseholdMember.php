<?php

namespace App\Household\Domain\Model;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity]
#[ORM\Table(name: 'household_members')]
class HouseholdMember
{
    public const TYPE_ADULT = 'adult';
    public const TYPE_CHILD = 'child';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Household::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'household_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Household $household;

    #[ORM\Column(type: 'string', length: 120)]
    private string $displayName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $memberType;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $birthDate;

    #[ORM\Column(type: 'string', length: 24, nullable: true)]
    private ?string $color;

    #[ORM\Column(type: 'boolean')]
    private bool $active;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(string $id, Household $household, string $displayName, string $memberType, ?DateTimeImmutable $birthDate, ?string $color)
    {
        if (!in_array($memberType, [self::TYPE_ADULT, self::TYPE_CHILD], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported household member type "%s".', $memberType));
        }

        $this->id = $id;
        $this->household = $household;
        $this->displayName = $displayName;
        $this->memberType = $memberType;
        $this->birthDate = $birthDate;
        $this->color = $color;
        $this->active = true;
        $this->createdAt = new DateTimeImmutable();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->household->id();
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    public function memberType(): string
    {
        return $this->memberType;
    }

    public function birthDate(): ?DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
