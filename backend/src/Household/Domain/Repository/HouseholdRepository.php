<?php

namespace App\Household\Domain\Repository;

use App\Household\Domain\Model\Household;

interface HouseholdRepository
{
    public function save(Household $household): void;

    public function get(string $householdId): Household;
}
