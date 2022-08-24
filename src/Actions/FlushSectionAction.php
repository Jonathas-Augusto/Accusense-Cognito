<?php

namespace Accusense\Cognito\Actions;

use Illuminate\Support\Facades\Cache;
use Accusense\Cognito\Actions\Contracts\DispatchableAction;

class FlushSectionAction implements DispatchableAction
{
    public function handle(...$args): void
    {
        $email = $args[0];

        Cache::forget($email);
    }
}
