<?php

namespace App\Shared\Application\Command;

interface CommandBus
{
    public function dispatch(Command $command): mixed;
}
