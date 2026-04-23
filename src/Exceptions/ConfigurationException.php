<?php

namespace App\Exceptions;

use Exception;

class ConfigurationException extends Exception
{
    public function __construct(string $message, int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
