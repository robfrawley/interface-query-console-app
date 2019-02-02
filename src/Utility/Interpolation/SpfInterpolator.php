<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utility\Interpolation;

final class SpfInterpolator extends AbstractInterpolator
{
    /**
     * @return string|null
     */
    protected function interpolate(): ?string
    {
        return @vsprintf($this->stringFormat, $replacements = array_values(iterator_to_array($this->getNormalizedReplacements())))
            ?: @vsprintf($this->cleanUpFormat($replacements), $replacements);
    }

    /**
     * @param array $replacements
     *
     * @return string
     */
    private function cleanUpFormat(array $replacements): string
    {
        $count = 0;
        $start = count($replacements);

        return preg_replace_callback(self::buildAnchorLocatorRegexp(), function ($match) use ($start, &$count) {
            return ++$count > $start ? self::describeAnchorType($match['type']) : $match[0];
        }, $this->stringFormat);
    }

    /**
     * @param string $anchor
     *
     * @return string
     */
    private static function describeAnchorType(string $anchor): string
    {
        foreach (static::getAnchorTypes() as $type => $list) {
            if (in_array($anchor, $list, true)) {
                $desc = sprintf('[undefined (%s)]', $type);
            }
        }

        return $desc ?? '[undefined]';
    }

    /**
     * @return string
     */
    private static function buildAnchorLocatorRegexp(): string
    {
        return sprintf(
            '{%%([0-9-]+)?(?<type>[%s])([0-9]?(?:\$[0-9]?[0-9]?[a-zA-Z]?)?)}',
            array_reduce(static::getAnchorTypes(), function (string $all, array $types) {
                return $all.implode('', $types);
            }, '')
        );
    }

    /**
     * @return array[]
     */
    private static function getAnchorTypes(): array
    {
        return [
            'string' => [
                's',
            ],
            'integer' => [
                'd',
                'u',
                'c',
                'o',
                'x',
                'X',
                'b',
            ],
            'double' => [
                'g',
                'G',
                'e',
                'E',
                'f',
                'F',
            ],
        ];
    }
}
