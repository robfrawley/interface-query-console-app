<?php

namespace App\Component\Configuration\Exception;

use SR\Exception\Runtime\RuntimeException;

final class LoadException extends RuntimeException
{
    /**
     * @param string          $stringFormat
     * @param array|null      $replacements
     * @param \Exception|null $previous
     */
    public function __construct(string $stringFormat, ?array $replacements = null, ?\Exception $previous = null)
    {
        parent::__construct(
            $stringFormat,
            ...($previous instanceof \Exception ? array_merge($replacements, [$previous]) : $replacements)
        );
    }
}

