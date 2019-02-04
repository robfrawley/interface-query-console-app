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

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

final class WwanDevicesCommand extends AbstractCommand
{
    /**
     * @param array $interfaces
     *
     * @return int
     */
    public function executeCommand(array $interfaces = []): int
    {
        return 255;
    }

    /**
     * @param InputDefinition $definition
     *
     * @return InputDefinition
     */
    protected function setupCommandInputDefinition(InputDefinition $definition): InputDefinition
    {
        $definition->addOption(
            new InputOption(self::OPT_MODE, ['m'], InputOption::VALUE_NONE,
                'Active interface operational mode.')
        );
        $definition->addOption(
            new InputOption(self::OPT_MODE_DESC, ['M'], InputOption::VALUE_NONE,
                'Concise description of the interface operational mode.')
        );
        $definition->addOption(
            new InputOption(self::OPT_SIGNAL_DBM, ['l'], InputOption::VALUE_NONE,
                'Signal level in dBm (decibel-milliwatts).')
        );
        $definition->addOption(
            new InputOption(self::OPT_SIGNAL_PERCENT, ['L'], InputOption::VALUE_NONE,
                'Signal level in percentage (using quadratic model derived from IPW2200 driver).')
        );

        return $definition;
    }
}
