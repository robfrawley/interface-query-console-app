<?php

/*
 * This file is part of the `src-run/vermicious-console-io-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utility\Interpolation;

final class PsrInterpolator extends AbstractInterpolator
{
    /**
     * @return string|null
     */
    protected function interpolate(): ?string
    {
        foreach ($this->getNormalizedReplacements() as $placeholder => $replacement) {
            $message = str_replace(sprintf('{%s}', $placeholder), $replacement, $message ?? $this->stringFormat);
        }

        return $message ?? null;
    }
}
