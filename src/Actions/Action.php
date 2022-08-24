<?php

namespace Accusense\Cognito\Actions;

use ErrorException;
use Accusense\Cognito\Actions\Contracts\DispatchableAction;

class Action
{
    public function dispatch(string $action, ...$args)
    {
        try {
            if (!in_array(DispatchableAction::class, class_implements($action))) {
                throw new ErrorException('Não é um dispatchable');
            }
            $action = new $action();
            $action->handle(...$args);
        } catch (ErrorException $e) {
            // lança erro
        }
    }
}
