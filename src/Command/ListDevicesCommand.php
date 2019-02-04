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

final class ListDevicesCommand extends AbstractCommand
{
    protected function configureCommand(): void
    {
        $this->enableInteractSanityChecks =
            $this->enableExecuteSanityChecks =
                $this->enableGeneralInputDefinition = false;
    }

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
        return $definition;
    }
}
