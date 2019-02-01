<?php

namespace App\Component\Configuration;

use Symfony\Component\Console\Command\Command;

final class CmdConfiguration extends Configuration
{
    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_TYPE = ['interface', 'type'];

    /**
     * @var string[]
     */
    protected const MAP_INTERFACE_FIND = ['interface', 'find'];

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
     * @param int|null $default
     *
     * @return string|null
     */
    public function getInterfaceType(?int $default = 0): ?string
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
     * @param int|null $default
     *
     * @return string|null
     */
    public function getInterfaceFind(?int $default = 0): ?string
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
        return strtolower(
            preg_replace('/(?<=\\w)(?=[A-Z])/', '-$1',
                preg_replace('/Command$/', '', (new \ReflectionObject($command))->getShortName())
            )
        );
    }
}
