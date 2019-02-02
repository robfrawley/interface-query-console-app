<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utility\Interpolation;

use App\Utility\Interpolation\Error\InterpolationError;
use SR\Dumper\VarDumper\ReturnDumper;
use SR\Exception\Logic\InvalidArgumentException;

abstract class AbstractInterpolator
{
    /**
     * @var string
     */
    protected $stringFormat;

    /**
     * @var array
     */
    protected $replacements;

    /**
     * @var \Closure|null
     */
    private $normalizer;

    /**
     * @var \Closure|null
     */
    private $errHandler;

    /**
     * @var InterpolationError[]
     */
    private $errHistory;

    /**
     * @param string        $stringFormat
     * @param array         $replacements
     * @param \Closure|null $normalizer
     * @param \Closure|null $errHandler
     */
    public function __construct(string $stringFormat, array $replacements = [], ?\Closure $normalizer = null, ?\Closure $errHandler = null)
    {
        $this->setStringFormat($stringFormat);
        $this->setReplacements($replacements);
        $this->setUpNormalizer($normalizer);
        $this->setUpErrHandler($errHandler);
        $this->resetErrHandler();
    }

    /**
     * @param string $format
     *
     * @return self
     */
    public function setStringFormat(string $format): self
    {
        $this->stringFormat = $format;

        return $this;
    }

    /**
     * @return self
     */
    public function resetStringFormat(): self
    {
        return $this->setStringFormat('');
    }

    /**
     * @param array $replacements
     *
     * @return self
     */
    public function setReplacements(array $replacements): self
    {
        $this->replacements = $replacements;

        return $this;
    }

    /**
     * @param mixed|mixed[] ...$replacements
     *
     * @return self
     */
    public function addReplacements(...$replacements): self
    {
        foreach ($replacements as $v) {
            $this->addReplacementMixed(...array_values((array) $v));
        }

        return $this;
    }

    /**
     * @param string|int $index
     * @param mixed      $value
     *
     * @return self
     */
    public function addReplacementNamed($index, $value): self
    {
        $this->replacements[$index] = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return self
     */
    public function addReplacementValue($value): self
    {
        $this->replacements[] = $value;

        return $this;
    }

    /**
     * @return self
     */
    public function resetReplacementValues(): self
    {
        return $this->setReplacements([]);
    }

    /**
     * @param \Closure|null $normalizer
     *
     * @return self
     */
    public function setUpNormalizer(?\Closure $normalizer = null): self
    {
        $this->normalizer = $normalizer ?? function (string $string): string {
            return $string;
        };

        return $this;
    }

    /**
     * @return self
     */
    public function resetNormalizer(): self
    {
        return $this->setUpNormalizer();
    }

    /**
     * @param \Closure|null $handler
     *
     * @return self
     */
    public function setUpErrHandler(?\Closure $handler = null): self
    {
        $this->errHandler = $handler ?? function (\Exception $exception = null) {
            $this->errHistory[] = new InterpolationError(
                $this->stringFormat, $this->replacements, new \DateTime(), $exception
            );

            return $this->stringFormat;
        };

        return $this;
    }

    /**
     * @return self
     */
    public function resetErrHandler(): self
    {
        return $this->setUpErrHandler();
    }

    /**
     * @return bool
     */
    public function hasErrorHistory(): bool
    {
        return count($this->errHistory) > 0;
    }

    /**
     * @return array[]
     */
    public function getErrorHistory(): array
    {
        return $this->errHistory;
    }

    /**
     * @return InterpolationError|null
     */
    public function popErrorHistory(): ?InterpolationError
    {
        return $this->hasErrorHistory() ? array_pop($this->errHistory) : null;
    }

    /**
     * @return self
     */
    public function resetErrorHistory(): self
    {
        $this->errHistory = [];

        return $this;
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        try {
            return ($this->normalizer)($this->interpolate()) ?? ($this->errHandler)();
        } catch (\RuntimeException $exception) {
            return ($this->errHandler)($exception);
        }
    }

    /**
     * @return string|null
     */
    abstract protected function interpolate(): ?string;

    /**
     * @return \Generator|mixed[]
     */
    protected function getNormalizedReplacements(): \Generator
    {
        foreach ($this->replacements as $i => $v) {
            yield $i => is_scalar($v) ? $v : (new ReturnDumper())->dump($v);
        }
    }

    /**
     * @param mixed      $a
     * @param mixed|null $b
     *
     * @return self
     */
    private function addReplacementMixed($a, $b = null): self
    {
        if (func_num_args() > 2) {
            throw new InvalidArgumentException(
                'Encountered a malformed replacement argument: expected it to either be a scalar value type (string, '.
                'integer, etc) or an array containing two elements (its first element as the replacement key and its'.
                'second as the value to interpolate). Instead, a %d-element-length array was provided.', func_num_args()
            );
        }

        return null === $b
            ? $this->addReplacementValue($a)
            : $this->addReplacementNamed($a, $b);
    }
}
