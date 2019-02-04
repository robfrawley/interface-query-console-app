<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Application;

use App\Command\AbstractCommand;
use App\Command\EthernetDevicesCommand;
use App\Command\ListDevicesCommand;
use App\Command\WirelessDevicesCommand;
use App\Command\WwanDevicesCommand;
use App\Component\Configuration\AppConfiguration;
use App\Component\Console\Style\Style;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends SymfonyApplication
{
    /**
     * @var string[]
     */
    private static $commandClasses = [
        ListDevicesCommand::class,
        EthernetDevicesCommand::class,
        WirelessDevicesCommand::class,
        WwanDevicesCommand::class,
    ];

    /**
     * @var AppConfiguration
     */
    private $configuration;

    /**
     * @var Style
     */
    private $style;

    /**
     * Application constructor provides parent name and version string from app configuration and auto-registers enabled
     * commands using cmd configuration.
     */
    public function __construct()
    {
        parent::__construct(
            $this->c()->getName(),
            $this->c()->stringifyVersion()
        );

        $this->addCommands(self::instantiateCommands());
    }

    /**
     * @return AppConfiguration
     */
    public function c(): AppConfiguration
    {
        return null !== $this->configuration
            ? $this->configuration
            : $this->configuration = new AppConfiguration();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Throwable
     *
     * @return int|null
     */
    public function doRun(InputInterface $input, OutputInterface $output): ?int
    {
        $this->style = new Style($input, $output);

        return parent::doRun($input, $output);
    }

    /**
     * @return string
     */
    public function getLongVersion(): string
    {
        return self::implodeMarkupLineSections([
            $this->getInfoTextNameMarkup(),
            $this->getInfoTextVersionMarkup(),
            $this->getInfoTextAuthorMarkup(),
            $this->getInfoTextLicenseMarkup(),
        ]);
    }

    /**
     * @return AbstractCommand[]
     */
    private static function instantiateCommands(): array
    {
        return array_filter(array_map(function (string $object): Command {
            return new $object();
        }, self::$commandClasses), function (Command $command): bool {
            return $command instanceof AbstractCommand;
        });
    }

    /**
     * @return string
     */
    private function getInfoTextNameMarkup(): string
    {
        return sprintf(' <fg=white;options=bold>%s</>', $this->c()->getName());
    }

    /**
     * @return string
     */
    private function getInfoTextVersionMarkup(): string
    {
        $data = [
            sprintf('version <fg=white;options=bold>%s</>', $this->c()->stringifyVersion(null, true, false)),
        ];

        $this->style->ifDebug(function () use (&$data) {
            if (null !== $name = $this->c()->getVersionNamed()) {
                array_push($data, sprintf('(%s)', $name));
            }
        });

        return self::implodeMarkupLineSections($data);
    }

    /**
     * @return string|null
     */
    private function getInfoTextAuthorMarkup(): ?string
    {
        $data = [
            sprintf('authored by <fg=white;options=bold>%s</>', $this->c()->getAuthorName('Unspecified Author')),
        ];

        $this->style->ifDebug(function () use (&$data) {
            if (null !== $mail = $this->c()->getAuthorMail()) {
                array_push($data, sprintf('\<%s>', $mail));
            }
        });

        $this->style->ifVeryVerbose(function () use (&$data) {
            if (null !== $link = $this->c()->getAuthorLink()) {
                array_push($data, sprintf('(%s)', $link));
            }
        });

        return $this->c()->hasAuthorName()
            ? self::implodeMarkupLineSections($data)
            : null;
    }

    /**
     * @return string|null
     */
    private function getInfoTextLicenseMarkup(): ?string
    {
        $data = [];

        $this->style->ifVerbose(function () use (&$data) {
            $data[] = sprintf(
                'under the <fg=white;options=bold>%s</>', $this->c()->getLicenseName('Unspecified License')
            );
        });

        $this->style->ifVeryVerbose(function () use (&$data) {
            if (null !== $link = $this->c()->getLicenseLink()) {
                array_push($data, sprintf('(%s)', $link));
            }
        });

        return $this->c()->hasLicenseName()
            ? self::implodeMarkupLineSections($data)
            : null;
    }

    /**
     * @param array       $sections
     * @param string|null $separator
     *
     * @return string
     */
    private static function implodeMarkupLineSections(array $sections = [], string $separator = null): string
    {
        return implode($separator ?? ' ', array_filter($sections));
    }
}
