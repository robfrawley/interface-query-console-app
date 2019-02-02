<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration\Exception;

use App\Application\Application;
use App\Command\Command;
use SR\Exception\Runtime\RuntimeException;

class AppRuntimeException extends RuntimeException
{
    /**
     * @var Application|null
     */
    private $app;

    /**
     * @var Command|null
     */
    private $cmd;

    /**
     * @param Application|null $app
     * @param Command|null     $cmd
     * @param string|null      $stringFormat
     * @param mixed            ...$arguments
     */
    public function __construct(?Application $app = null, ?Command $cmd = null, ?string $stringFormat = null, ...$arguments)
    {
        parent::__construct($stringFormat, $arguments);

        $this->app = $app;
        $this->cmd = $cmd;
    }

    /**
     * @return bool
     */
    public function hasApp(): bool
    {
        return $this->app instanceof Application;
    }

    /**
     * @return Application|null
     */
    public function getApp(): ?Application
    {
        return $this->app;
    }

    /**
     * @return bool
     */
    public function hasCmd(): bool
    {
        return $this->cmd instanceof Application;
    }

    /**
     * @return Command|null
     */
    public function getCmd(): ?Command
    {
        return $this->cmd;
    }
}
