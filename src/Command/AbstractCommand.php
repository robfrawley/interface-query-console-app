<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Application\Application;
use App\Component\Configuration\AppConfiguration;
use App\Component\Configuration\CmdConfiguration;
use App\Component\Console\Style\Style;
use App\Component\Filesystem\Path;
use SR\Exception\Runtime\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
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
     * @var CmdConfiguration
     */
    protected $configuration;

    /**
     * @var string
     */
    private static $sysFsNetRootPath = '/sys/class/net';

    /**
     * @var Style
     */
    private $style;

    /**
     * Setup command name, description, and options/arguments definition.
     */
    public function configure(): void
    {
        $this->setName($this->c()->getCall());
        $this->setDescription($this->c()->getDesc());
        $this->setDefinition($this->setupInputDefinition());
    }

    /**
     * @param InputInterface|null  $i
     * @param OutputInterface|null $o
     *
     * @return Style
     */
    final public function s(InputInterface $i = null, OutputInterface $o = null): Style
    {
        return null === $i || null === $o
            ? $this->style
            : $this->style = new Style($i, $o);
    }

    /**
     * @return CmdConfiguration
     */
    final public function c(): CmdConfiguration
    {
        return null !== $this->configuration
            ? $this->configuration
            : $this->configuration = new CmdConfiguration(CmdConfiguration::resolveCommandContext($this));
    }

    /**
     * @return Application
     */
    final public function getApplication(): Application
    {
        try {
            ($p = (new \ReflectionClass(Command::class))->getProperty('application'))
                ->setAccessible(true);

            return $p->getValue($this);
        } catch (\ReflectionException $exception) {
            throw new RuntimeException('Failed to resolve private "application" property of parent Command class.');
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    final protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->s($input, $output);
        $this->s()->applicationTitle($this);

        $interfaces = $this->sanitizeInterfaces($input->getArgument(self::ARG_INTERFACE_NAME));

        while (empty($interfaces)) {
            $interfaces = $this->sanitizeInterfaces(
                $this->interactiveInterfaceSelection($input, $output, 'wireless', $this->getInterfaceTypeFilterClosure())
            );
        }

        $input->setArgument(self::ARG_INTERFACE_NAME, $interfaces);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->s($input, $output);

        if (0 === count($interfaces = $input->getArgument(self::ARG_INTERFACE_NAME))) {
            $this->s()->error(vsprintf('You must specify one or more %s interface names (valid %1$s interfaces match the regex "%s").', [
                $this->c()->getInterfaceType(),
                $this->c()->getInterfaceFind(),
            ]));

            return 255;
        }

        return $this->executeCommand($this->sanitizeInterfaces($interfaces), $input, $output);
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
     * @param array $inputted
     *
     * @return array
     */
    protected function sanitizeInterfaces(array $inputted): array
    {
        $existing = array_values(array_filter(
            $inputted, $this->getInterfaceRealFilterClosure()
        ));

        if (!empty($d = array_diff($inputted, $existing))) {
            $this->notifyFilteredInterfaceDiff($d, 'Removed unknown-named interfaces: %s (valid %s interfaces found in "%s/*").',
                $this->c()->getInterfaceType(), self::$sysFsNetRootPath);
        }

        $matching = array_values(array_filter(
            $existing, $this->getInterfaceTypeFilterClosure()
        ));

        if (!empty($d = array_diff($existing, $matching))) {
            $this->notifyFilteredInterfaceDiff($d, 'Removed invalid-typed interfaces: %s (valid %s interfaces match the regex "%s").',
                $this->c()->getInterfaceType(), $this->c()->getInterfaceFind());
        }

        return $matching;
    }

    /**
     * @param array  $listing
     * @param string $format
     * @param mixed  ...$replacements
     */
    protected function notifyFilteredInterfaceDiff(array $listing, string $format, ...$replacements): void
    {
        $this->s()->ifVeryVerbose(function (Style $s) use ($format, $listing, $replacements): void {
            $s->note(sprintf($format, $this->implodeQuotedList($listing), ...$replacements));
        });
    }

    /**
     * @return \Closure
     */
    protected function getInterfaceTypeFilterClosure(): \Closure
    {
        return function (string $name): bool {
            return 1 === preg_match(sprintf('{%s}', $this->c()->getInterfaceFind()), $name);
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
     * @param InputDefinition $definition
     *
     * @return InputDefinition
     */
    abstract protected function setupCommandInputDefinition(InputDefinition $definition): InputDefinition;

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
     * @param int             $default
     *
     * @return array
     * @return array
     */
    private function interactiveInterfaceSelection(InputInterface $input, OutputInterface $output, string $contextName, \Closure $contextFilter = null, int $default = 0): array
    {
        $interfaces = $this->getSystemNetworkInterfaces($contextFilter);

        if (empty($interfaces)) {
            $this->s()->error(sprintf('Unable to locate any %s interfaces on your system! Exiting...', $contextName));
            exit(255);
        }

        if (1 === count($interfaces)) {
            $this->s()->ifVerbose(function (Style $s) use ($interfaces, $default): void {
                $s->comment(sprintf(
                    'Auto-selecting %s interface "%s" as no other selection options exist (no valid interface names '.
                    'were explicitly passed as arguments and only one %1$s interface was found on this system).',
                    $this->c()->getInterfaceType(), $this->s()->markupBold($interfaces[$default])
                ));
            });

            if (!$this->s()->confirm(sprintf('Continue using the "%s" auto-selected %s interface?', $interfaces[$default], $this->c()->getInterfaceType()))) {
                $this->s()->error(sprintf(
                    'Cannot continue without a valid %s interface (the auto-selected "%s" interface is the only valid %1$s interface found on this system)!',
                    $this->c()->getInterfaceType(), $interfaces[$default]
                ));
                exit(255);
            }

            return $interfaces;
        }

        $this->s()->ifVerbose(function (Style $s) use ($contextName): void {
            $s->comment(sprintf(
                'No named %s interfaces were passed as command-line arguments to this script; attempting to determine '.
                'this required information by falling back to interactive questioning.', $contextName
            ));
        });

        $result = $this->s()->askQuestion((new ChoiceQuestion(sprintf(
            '<fg=green>Select the %s interface(s) you would like to query metrics from (defaulting to "%d" as "%s")</>:',
            $this->c()->getInterfaceType(), $default, $interfaces[$default]
        ), $interfaces, '0'))->setMultiselect(true));

        $this->s()->newLine();
        $this->s()->ifVerbose(function (Style $s) use ($contextName, $result): void {
            $s->comment(sprintf(
                    'You selected the following %s interface(s) interactively: %s.', $contextName, $this->implodeQuotedList($result))
            );
        });

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
     * @param InputDefinition $definition
     *
     * @return InputDefinition
     */
    private function setupInputDefinition(InputDefinition $definition = null): InputDefinition
    {
        return $this->setupCommandInputDefinition(
            $this->setupGeneralInputDefinition($definition ?? new InputDefinition())
        );
    }

    /**
     * @param InputDefinition $definition
     *
     * @return InputDefinition
     */
    private function setupGeneralInputDefinition(InputDefinition $definition): InputDefinition
    {
        $definition->addArgument(
            new InputArgument(self::ARG_INTERFACE_NAME, InputArgument::IS_ARRAY,
                'Named network interfaces to query for the requested information.')
        );

        return $definition;
    }
}
