<?php

namespace Accusense\Cognito\Actions;

use Illuminate\Support\Facades\Cache;
use Accusense\Cognito\Actions\Contracts\DispatchableAction;

class SetSectionAction implements DispatchableAction
{
    public function handle(...$args): void
    {
        Cache::put($args[0], $args[1], $args[2]);
    }
}
