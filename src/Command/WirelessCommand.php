<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class WirelessCommand extends Command
{
    /**
     * @var string
     */
    private const OPT_MODE = 'mode';

    /**
     * @var string
     */
    private const OPT_MODE_DESC = 'mode-desc';

    /**
     * @var string
     */
    private const OPT_ESSID = 'essid';

    /**
     * @var string
     */
    private const OPT_SIGNAL_DBM = 'signal-dbm';

    /**
     * @var string
     */
    private const OPT_SIGNAL_PERCENT = 'signal-percent';

    /**
     * @var string
     */
    private const ARG_INTERFACE_NAME = 'interface-name';

    /**
     * @var int[]
     */
    private static $dbmToPercentMap = [
        -1 => 100,
        -2 => 100,
        -3 => 100,
        -4 => 100,
        -5 => 100,
        -6 => 100,
        -7 => 100,
        -8 => 100,
        -9 => 100,
        -10 => 100,
        -11 => 100,
        -12 => 100,
        -13 => 100,
        -14 => 100,
        -15 => 100,
        -16 => 100,
        -17 => 100,
        -18 => 100,
        -19 => 100,
        -20 => 100,
        -21 => 99,
        -22 => 99,
        -23 => 99,
        -24 => 98,
        -25 => 98,
        -26 => 98,
        -27 => 97,
        -28 => 97,
        -29 => 96,
        -30 => 96,
        -31 => 95,
        -32 => 95,
        -33 => 94,
        -34 => 93,
        -35 => 93,
        -36 => 92,
        -37 => 91,
        -38 => 90,
        -39 => 90,
        -40 => 89,
        -41 => 88,
        -42 => 87,
        -43 => 86,
        -44 => 85,
        -45 => 84,
        -46 => 83,
        -47 => 82,
        -48 => 81,
        -49 => 80,
        -50 => 79,
        -51 => 78,
        -52 => 76,
        -53 => 75,
        -54 => 74,
        -55 => 73,
        -56 => 71,
        -57 => 70,
        -58 => 69,
        -59 => 67,
        -60 => 66,
        -61 => 64,
        -62 => 63,
        -63 => 61,
        -64 => 60,
        -65 => 58,
        -66 => 56,
        -67 => 55,
        -68 => 53,
        -69 => 51,
        -70 => 50,
        -71 => 48,
        -72 => 46,
        -73 => 44,
        -74 => 42,
        -75 => 40,
        -76 => 38,
        -77 => 36,
        -78 => 34,
        -79 => 32,
        -80 => 30,
        -81 => 28,
        -82 => 26,
        -83 => 24,
        -84 => 22,
        -85 => 20,
        -86 => 17,
        -87 => 15,
        -88 => 13,
        -89 => 10,
        -90 => 8,
        -91 => 6,
        -92 => 3,
        -93 => 1,
        -94 => 1,
        -95 => 1,
        -96 => 1,
        -97 => 1,
        -98 => 1,
        -99 => 1,
        -100 => 1,
    ];

    /**
     * @var StyleInterface
     */
    private $style;

    /**
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        parent::__construct($name ?? 'wireless');
    }

    public function configure(): void
    {
        $this
            ->setName('wireless')
            ->setDescription('Retrieve requested data values for the specified wireless interfaces')
            ->setDefinition($this->createCommandDefinition());
    }

    /**
     * @return InputDefinition
     */
    private function createCommandDefinition(): InputDefinition
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->style = new SymfonyStyle($input, $output);

        if (0 === count($interfaces = $input->getArgument('interface-name'))) {
            $this->style->error('You must specify one or more interface names.');
            return 255;
        }

        $data = array_filter(array_map(function (string $name) use ($input) {
            return $this->getInterfaceData($name, $input);
        }, $interfaces));

        dump($data);

        return 0;
    }

    private function getInterfaceData(string $name, InputInterface $input): ?array
    {
        if (!$this->isValidInterfaceName($name)) {
            $this->style->warning(
                sprintf('Specified interface name is invalid: "%s"', $name)
            );

            return null;
        }

        $raw = $this->getInterfaceRawData($name);
        $row = [];

        if ($input->hasOption('signal-dbm')) {
            $row[] = $this->extractSignalLevel($raw);
        }

        return $row;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isValidInterfaceName(string $name): bool
    {
        return is_dir(sprintf('/sys/class/net/%s', $name));
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    private function getInterfaceRawData(string $name): ?string
    {
        $process = new Process(['iwconfig', $name]);

        try {
            $process->run();
        } catch (RuntimeException $exception) {
            $this->style->warning(sprintf('Failed to call "iwconfig" to resolve interface details: "%s"', $name));
        } finally {
            return $process->getOutput();
        }
    }

    /**
     * @param string $data
     *
     * @return int|null
     */
    private function extractSignalLevel(string $data): ?int
    {
        if (preg_match('/Signal\slevel=(?<level>-?[0-9]+)/', $data, $match)) {
            return $match['level'];
        }

        return null;
    }
}
