<?php

namespace App\Application;

use App\Command\WirelessCommand;
use App\Component\Configuration\AppConfiguration;
use App\Component\Configuration\Exception\AppLoadConfigException;
use App\Component\Configuration\Exception\LoadException;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    /**
     * @var AppConfiguration
     */
    private $config;

    public function __construct()
    {
        $this->config = $this->initializeConfiguration();

        parent::__construct(
            $this->config->getName(),
            $this->config->stringifyVersion()
        );

        $this->initializeCommands(
            WirelessCommand::class
        );
    }

    /**
     * @return AppConfiguration
     */
    private function initializeConfiguration(): AppConfiguration
    {
        try {
            $config = new AppConfiguration();
        } catch (LoadException $exception) {
            throw new AppLoadConfigException(
                $this, null, 'Failed to load application "%s" YAML config file: "%s"', get_called_class(), (string) $config
            );
        } finally {
            return $config;
        }
    }

    /**
     * @param string ...$classList
     */
    private function initializeCommands(string ...$classList): void
    {
        foreach ($classList as $class) {
            $this->add(new $class($this->config));
        }
    }
}
