<?php

namespace App\Command;

use App\Component\Configuration\AppConfiguration;
use App\Component\Configuration\CmdConfiguration;
use App\Component\Filesystem\Path;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{
    /**
     * @var string
     */
    protected const OPT_MODE = 'mode';

    /**
     * @var string
     */
    protected const OPT_MODE_DESC = 'mode-desc';

    /**
     * @var string
     */
    protected const OPT_ESSID = 'essid';

    /**
     * @var string
     */
    protected const OPT_SIGNAL_DBM = 'signal-dbm';

    /**
     * @var string
     */
    protected const OPT_SIGNAL_PERCENT = 'signal-percent';

    /**
     * @var string
     */
    protected const ARG_INTERFACE_NAME = 'interface-name';

    /**
     * @var string
     */
    private static $sysFsNetRootPath = '/sys/class/net';

    /**
     * @var StyleInterface
     */
    private $style;

    /**
     * @var AppConfiguration
     */
    protected $cfgApp;

    /**
     * @var CmdConfiguration
     */
    protected $cfgCmd;

    /**
     * Setup command name, description, and options/arguments definition.
     */
    public function configure(): void
    {
        $this->setName($this->cfgCmd->getName());
        $this->setDescription($this->cfgCmd->getDesc());
        $this->setDefinition($this->createCommandDefinition());
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    final public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->s($input, $output);

        if (0 === count($interfaces = $input->getArgument(self::ARG_INTERFACE_NAME))) {
            $this->s()->error(vsprintf('You must specify one or more %s interface names (those beginning with "%s").', [
                $this->cfgCmd->getInterfaceType(),
                $this->cfgCmd->getInterfaceFind(),
            ]));

            return 255;
        }

        return $this->executeCommand($interfaces, $input, $output);
    }

    /**
     * @param array           $interface
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    abstract protected function executeCommand(array $interface, InputInterface $input, OutputInterface $output): int;

    /**
     * @param AppConfiguration $config
     */
    protected function initializeConfiguration(AppConfiguration $config)
    {
        $this->cfgApp = $config;
        $this->cfgCmd = new CmdConfiguration(
            CmdConfiguration::resolveCommandContext($this)
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->s($input, $output);

        $interfaces = $this->sanitizeInterfaces(
            $input->getArgument(self::ARG_INTERFACE_NAME)
        );

        while (empty($interfaces)) {
            $interfaces = $this->sanitizeInterfaces(
                $this->interactiveInterfaceSelection(
                    $input, $output, 'wireless', $this->getInterfaceTypeFilterClosure()
                )
            );
        }

        $input->setArgument(self::ARG_INTERFACE_NAME, $interfaces);
    }

    /**
     * @param array $user
     *
     * @return array
     */
    protected function sanitizeInterfaces(array $user): array
    {
        $real = array_values(array_filter(
            $user, $this->getInterfaceRealFilterClosure()
        ));

        $this->notifyFilteredInterfaceDiff(
            $user,
            $real,
            'Removed unknown-named interfaces: %s. Valid %s interfaces found in "%s/*".',
            $this->cfgCmd->getInterfaceType(),
            self::$sysFsNetRootPath
        );

        $type = array_values(array_filter(
            $real, $this->getInterfaceTypeFilterClosure()
        ));

        $this->notifyFilteredInterfaceDiff(
            $real,
            $type,
            'Removed invalid-typed interfaces: %s. Valid %s interfaces match the regex "^%s".',
            $this->cfgCmd->getInterfaceType(),
            $this->cfgCmd->getInterfaceFind()
        );

        return $type;
    }

    /**
     * @param array  $listOne
     * @param array  $listTwo
     * @param string $format
     * @param mixed  ...$replacements
     */
    protected function notifyFilteredInterfaceDiff(array $listOne, array $listTwo, string $format, ...$replacements): void
    {
        $this->s()->warning(
            sprintf($format, $this->implodeQuotedList(array_diff($listOne, $listTwo)), ...$replacements)
        );
    }

    /**
     * @return \Closure
     */
    protected function getInterfaceTypeFilterClosure(): \Closure
    {
        return function (string $name): bool {
            return 0 === mb_strpos($name, $this->cfgCmd->getInterfaceFind());
        };
    }

    /**
     * @return \Closure
     */
    protected function getInterfaceRealFilterClosure(): \Closure
    {
        return function (string $name): bool {
            return ($path = new Path(self::$sysFsNetRootPath, $name))->isPathType()
                && ($path->isExisting() && $path->isReadable());
        };
    }

    /**
     * @return InputDefinition
     */
    abstract protected function createCommandDefinition(): InputDefinition;

    /**
     * @param InputInterface|null  $i
     * @param OutputInterface|null $o
     *
     * @return StyleInterface
     */
    protected function s(InputInterface $i = null, OutputInterface $o = null): StyleInterface
    {
        return null === $i || null === $o
            ? $this->style
            : $this->style = new SymfonyStyle($i, $o);
    }

    /**
     * @param Process       $process
     * @param \Closure|null $filter
     * @param \Closure|null $mapper
     *
     * @return array
     */
    protected function getProcessOutputAsArray(Process $process, \Closure $filter = null, \Closure $mapper = null): array
    {
        return $process->isSuccessful() ? array_values(array_filter(
            array_map(
                $mapper ?? function (string $line): string {
                    return trim($line);
                },
                explode(
                    PHP_EOL, $process->getOutput()
                )
            ),
            $filter ?? function (string $line): string {
                return true;
            }
        )) : [];
    }

    /**
     * @param string         $name
     * @param InputInterface $input
     *
     * @return array|null
     */
    protected function getInterfaceData(string $name, InputInterface $input): ?array
    {
        if (!$this->getInterfaceRealFilterClosure()($name)) {
            $this->s()->warning(
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
     * @param array $list
     *
     * @return string
     */
    protected function implodeQuotedList(array $list): string
    {
        return implode(', ', array_map(function (string $name): string {
            return sprintf('"%s"', $name);
        }, $list));
    }

    /**
     * @param string ...$cli
     *
     * @return Process
     */
    protected static function makeProcess(string ...$cli): Process
    {
        return new Process($cli);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $contextName
     * @param \Closure|null   $contextFilter
     *
     * @return array
     */
    private function interactiveInterfaceSelection(InputInterface $input, OutputInterface $output, string $contextName, \Closure $contextFilter = null): array
    {
        $interfaces = $this->getSystemNetworkInterfaces($contextFilter);

        if (empty($interfaces)) {
            $this->s()->error(sprintf('Unable to locate any %s interfaces on your system! Exiting...', $contextName));
            exit(255);
        }

        $this->s()->warning(sprintf(
            'No named %s interfaces were passed as command-line arguments to this script; attempting to determine '.
            'this required information by falling back to interactive questioning.', $contextName
        ));

        $result = $this->getHelper('question')->ask($input, $output, (new ChoiceQuestion(sprintf(
            '<fg=green>Select the %s interface(s) you would like to query against and display the requested metrics for</>:',
            $this->cfgCmd->getInterfaceType()
        ), $interfaces, '0'))->setMultiselect(true));

        $this->s()->newLine();

        if ($output->isVerbose()) {
            $this->s()->note(sprintf(
                'You selected the following %s interface(s): %s', $contextName, $this->implodeQuotedList($result))
            );
        }

        return $result;
    }

    /**
     * @param \Closure|null $filter
     * @param \Closure|null $mapper
     *
     * @return array
     */
    private function getSystemNetworkInterfaces(\Closure $filter = null, \Closure $mapper = null): array
    {
        ($process = self::makeProcess(
            'ls',
            '-1',
            self::$sysFsNetRootPath
        ))->run();

        return $this->getProcessOutputAsArray(
            $process, $filter, $mapper
        );
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    private function getInterfaceRawData(string $name): ?string
    {
        $process = self::makeProcess('iwconfig', $name);

        try {
            $process->run();
        } catch (RuntimeException $exception) {
            $this->s()->warning(sprintf('Failed to call "iwconfig" to resolve interface details: "%s"', $name));
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
