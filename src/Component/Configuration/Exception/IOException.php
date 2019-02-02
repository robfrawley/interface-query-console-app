<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration\Exception;

use App\Utility\Interpolation\SpfInterpolator;
use Symfony\Component\Filesystem\Exception\IOException as SymfonyIOException;

final class IOException extends SymfonyIOException
{
    /**
     * @param string          $stringFormat
     * @param array|null      $replacements
     * @param \Exception|null $previous
     * @param string|null     $path
     */
    public function __construct(string $stringFormat, ?array $replacements = null, ?\Exception $previous = null, ?string $path = null)
    {
        parent::__construct((new SpfInterpolator($stringFormat, $replacements))->compile(), 9999, $previous, $path);
    }
}
