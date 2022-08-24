<?php

namespace Accusense\Cognito\Exceptions;

use Exception;

class CognitoException extends Exception
{

    public $errors;
    public $status = 401;

    public function __construct($errors, int $status = 401)
    {
        parent::__construct('cognito error');

        $this->errors = $errors;
        $this->status = $status;
    }
}
