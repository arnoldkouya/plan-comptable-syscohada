<?php

declare(strict_types=1);

namespace Syscohada\Exceptions;

class CompteNotFoundException extends \RuntimeException
{
    public function __construct(string $code)
    {
        parent::__construct("Compte SYSCOHADA introuvable : « {$code} »");
    }
}
