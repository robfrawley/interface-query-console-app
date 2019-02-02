<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration\Resolver;

use App\Component\Configuration\Configuration;
use App\Component\Configuration\Resolver\Result\ConfigResolverResult;

final class ConfigResolver
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var string
     */
    private $keysDelimiter;

    /**
     * @param Configuration $configuration
     * @param string|null   $keyDelimiterC
     */
    public function __construct(Configuration $configuration, string $keyDelimiterC = null)
    {
        $this->configuration = $configuration;
        $this->keysDelimiter = $keyDelimiterC ?? ':';
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->configuration->__toString();
    }

    /**
     * @param string ...$mapping
     *
     * @return mixed|null
     */
    public function find(string ...$mapping)
    {
        return new ConfigResolverResult(
            (!empty($m = $this->systematizeMapping(...$mapping)))
                ? $this->configuration->get(...$m)
                : null
        );
    }

    /**
     * @param array $mapping
     *
     * @return array
     */
    public function systematizeMapping(string ...$mapping): array
    {
        $systematized = [];

        foreach ($mapping as $m) {
            $systematized = array_merge(
                $systematized, $this->explodeConcatenatedMapping($m)
            );
        }

        return array_filter($systematized);
    }

    /**
     * @param string ...$mapping
     *
     * @return string
     */
    public function concatenateMapping(string ...$mapping): string
    {
        return implode($this->keysDelimiter, $this->systematizeMapping(
            ...$this->namespaceMapping($mapping)
        ));
    }

    /**
     * @param array $mapping
     *
     * @return array
     */
    private function namespaceMapping(array $mapping): array
    {
        if (!empty($ns = $this->configuration->getNamespace())) {
            array_unshift($mapping, ...$ns);
        }

        return $mapping;
    }

    /**
     * @param string $mapping
     *
     * @return string[]
     */
    private function explodeConcatenatedMapping(string $mapping): array
    {
        return (array) (
            false === mb_strpos($mapping, $this->keysDelimiter)
                ? $mapping : explode($this->keysDelimiter, $mapping)
        );
    }
}
