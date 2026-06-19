<?php

namespace App\Household\Domain\Model;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'households')]
class Household
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 120)]
    private string $name;

    #[ORM\Column(type: 'string', length: 3)]
    private string $defaultCurrency;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, HouseholdMember>
     */
    #[ORM\OneToMany(mappedBy: 'household', targetEntity: HouseholdMember::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $members;

    public function __construct(string $id, string $name, string $defaultCurrency = 'PLN')
    {
        $this->id = $id;
        $this->name = $name;
        $this->defaultCurrency = $defaultCurrency;
        $this->createdAt = new DateTimeImmutable();
        $this->members = new ArrayCollection();
    }

    public function addMember(string $memberId, string $displayName, string $memberType, ?DateTimeImmutable $birthDate, ?string $color): HouseholdMember
    {
        $member = new HouseholdMember($memberId, $this, $displayName, $memberType, $birthDate, $color);
        $this->members->add($member);

        return $member;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function defaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return list<HouseholdMember>
     */
    public function members(): array
    {
        return $this->members->toArray();
    }
}
