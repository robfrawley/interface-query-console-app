<?php

namespace App\Command;

use App\Component\Configuration\AppConfiguration;
use App\Component\Configuration\CmdConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WirelessCommand extends AbstractCommand
{
    /**
     * @param AppConfiguration $config
     */
    public function __construct(AppConfiguration $config)
    {
        $this->initializeConfiguration($config);
        parent::__construct();
    }

    /**
     * @param array           $interfaces
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function executeCommand(array $interfaces, InputInterface $input, OutputInterface $output): int
    {
        $data = array_filter(array_map(function (string $name) use ($input) {
            return $this->getInterfaceData($name, $input);
        }, $interfaces));

        dump($data);

        return 0;
    }

    /**
     * @return InputDefinition
     */
    protected function createCommandDefinition(): InputDefinition
    {
        $definition = new InputDefinition();

        $definition->addOption(
            new InputOption(self::OPT_MODE, ['m'], InputOption::VALUE_NONE, 'Active interface operational mode.')
        );
        $definition->addOption(
            new InputOption(self::OPT_MODE_DESC, ['M'], InputOption::VALUE_NONE, 'Concise description of the interface operational mode.')
        );
        $definition->addOption(
            new InputOption(self::OPT_ESSID, ['e'], InputOption::VALUE_NONE, 'Extended service set identifier (ESSID) or service set identifier (SSID).')
        );
        $definition->addOption(
            new InputOption(self::OPT_SIGNAL_DBM, ['l'], InputOption::VALUE_NONE, 'Signal level in dBm (decibel-milliwatts).')
        );
        $definition->addOption(
            new InputOption(self::OPT_SIGNAL_PERCENT, ['L'], InputOption::VALUE_NONE, 'Signal level in percentage (using quadratic model derived from IPW2200 driver).')
        );
        $definition->addArgument(
            new InputArgument(self::ARG_INTERFACE_NAME, InputArgument::IS_ARRAY, 'Named network interfaces to query for the requested information.')
        );

        return $definition;
    }
}
