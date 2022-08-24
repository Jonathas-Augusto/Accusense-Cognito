<?php

namespace Accusense\Cognito\Actions\Contracts;

interface DispatchableAction
{
    public function handle(...$args): void;
}
