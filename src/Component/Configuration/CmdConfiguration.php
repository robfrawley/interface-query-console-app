<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration;

final class CmdConfiguration extends Configuration
{
    /**
     * @var string[]
     */
    protected const MAP_LOAD = ['load'];

    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_TYPE = ['interface', 'category'];

    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_FIND = ['interface', 'validate', 'name_matches_type'];

    /**
     * @return bool
     */
    public function hasLoad(): bool
    {
        return null !== $this->getLoad(null);
    }

    /**
     * @param bool $default
     *
     * @return bool
     */
    public function getLoad(bool $default = false): bool
    {
        return $this->getValidOrDefault(
            self::useBooleanTypeChecker(), $default, ...self::MAP_LOAD
        );
    }

    /**
     * @return bool
     */
    public function hasInterfaceType(): bool
    {
        return null !== $this->getInterfaceType(null);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getInterfaceType(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_INTERFACE_TYPE
        );
    }

    /**
     * @return bool
     */
    public function hasInterfaceFind(): bool
    {
        return null !== $this->getInterfaceFind(null);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getInterfaceFind(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_INTERFACE_FIND
        );
    }

    /**
     * @param string ...$namespace
     *
     * @return string[]
     */
    protected function resolveNamespace(string ...$namespace): array
    {
        if (1 === count($namespace)) {
            array_unshift($namespace, 'application', 'commands');
        }

        return $namespace;
    }
}
