<?php

namespace App\Application;

use App\Command\WirelessCommand;
use App\Component\Configuration\AppConfiguration;
use Symfony\Component\Console\Application as SymfonyApplication;

class Application extends SymfonyApplication
{
    /**
     * @param string|null $name
     * @param string|null $version
     */
    public function __construct(string $name = null, string $version = null)
    {
        $appConfig = new AppConfiguration();
        dump($appConfig);

        parent::__construct(
            $name ?? 'Interface Query',
            $version ?? getenv('APP_VER') ?? '0.0.0'
        );

        $this->add(new WirelessCommand());
    }
}
