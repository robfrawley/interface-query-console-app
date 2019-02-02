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

use Symfony\Component\Console\Command\Command;

final class CmdConfiguration extends Configuration
{
    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_TYPE = ['interface', 'category'];

    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_FIND = ['interface', 'validate', 'name_matches_type'];

    /**
     * @param string $context
     */
    public function __construct(string $context)
    {
        parent::__construct(
            ...self::LOCATION_APP_CONFIG
        );

        $this->load();
        $this->setNamespace('application', 'commands', $context);
    }

    /**
     * @return bool
     */
    public function hasInterfaceType(): bool
    {
        return $this->has(...self::MAP_INTERFACE_TYPE);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getInterfaceType(?string $default = null): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_INTERFACE_TYPE
        );
    }

    /**
     * @return bool
     */
    public function hasInterfaceFind(): bool
    {
        return $this->has(...self::MAP_INTERFACE_FIND);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getInterfaceFind(?string $default = null): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_INTERFACE_FIND
        );
    }

    /**
     * @param Command $command
     *
     * @return string
     */
    public static function resolveCommandContext(Command $command): string
    {
        return mb_strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])/', '-$1',
                preg_replace('/Command$/', '', (new \ReflectionObject($command))->getShortName())
            )
        );
    }
}
