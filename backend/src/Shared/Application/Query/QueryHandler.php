<?php

namespace App\Shared\Application\Query;

interface QueryHandler
{
    public function handles(): string;
}
