<?php

namespace App\Shared\Application\Command;

interface CommandHandler
{
    public function handles(): string;
}
