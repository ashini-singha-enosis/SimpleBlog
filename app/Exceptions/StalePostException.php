<?php

namespace App\Exceptions;

use RuntimeException;

class StalePostException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This post was saved somewhere else. Reload and try again.');
    }
}
