<?php

namespace App\Expenses\Domain\Model;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expense_categories')]
#[ORM\UniqueConstraint(name: 'UNIQ_EXPENSE_CATEGORY_HOUSEHOLD_SLUG', columns: ['household_id', 'slug'])]
class ExpenseCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $householdId;

    #[ORM\Column(type: 'string', length: 80)]
    private string $name;

    #[ORM\Column(type: 'string', length: 80)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 7)]
    private string $color;

    public function __construct(string $id, string $householdId, string $name, string $slug, string $color)
    {
        $this->id = $id;
        $this->householdId = $householdId;
        $this->name = $name;
        $this->slug = $slug;
        $this->color = $color;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function householdId(): string
    {
        return $this->householdId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function color(): string
    {
        return $this->color;
    }
}
