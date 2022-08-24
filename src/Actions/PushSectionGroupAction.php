<?php

namespace Accusense\Cognito\Actions;

use Illuminate\Support\Facades\Cache;
use Accusense\Cognito\Actions\Contracts\DispatchableAction;

class PushSectionGroupAction implements DispatchableAction
{
    public function handle(...$args): void
    {
        $email = $args[0];
        $sectionToken = $args[1];

        $sections = Cache::get($email, []);
        $sections[] = $sectionToken;

        Cache::forget($email);
        Cache::put($email, $sections);
    }
}
