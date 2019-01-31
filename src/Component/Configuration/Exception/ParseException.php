<?php

namespace App\Component\Configuration\Exception;

use App\Utility\Interpolation\SpfInterpolator;
use Symfony\Component\Yaml\Exception\ParseException as SymfonyParseException;

final class ParseException extends SymfonyParseException
{
    /**
     * @param string          $stringFormat
     * @param array|null      $replacements
     * @param \Exception|null $previous
     */
    public function __construct(string $stringFormat, ?array $replacements = null, ?\Exception $previous = null)
    {
        parent::__construct(
            (new SpfInterpolator($stringFormat, $replacements))->compile(),
            ...($previous instanceof SymfonyParseException ? [
                $previous->getParsedLine(),
                $previous->getSnippet(),
                $previous->getParsedFile(),
            ] : [])
        );
    }
}
