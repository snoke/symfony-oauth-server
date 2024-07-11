<?php

namespace Snoke\OAuthServer\Exception;

use Throwable;

class AuthServerException extends \Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}