<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utility\Interpolation\Error;

final class InterpolationError
{
    /**
     * @var string
     */
    private $stringFormat;

    /**
     * @var mixed[]
     */
    private $replacements;

    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @var \Exception|null
     */
    private $exception;

    /**
     * @param string          $stringFormat
     * @param array           $replacements
     * @param \DateTime       $dateTime
     * @param \Exception|null $exception
     */
    public function __construct(string $stringFormat, array $replacements, \DateTime $dateTime, \Exception $exception = null)
    {
        $this->stringFormat = $stringFormat;
        $this->replacements = $replacements;
        $this->dateTime = $dateTime;
        $this->exception = $exception;
    }

    /**
     * @return string
     */
    public function getStringFormat(): string
    {
        return $this->stringFormat;
    }

    /**
     * @return mixed[]
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    /**
     * @return \Exception|null
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }
}
