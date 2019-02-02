<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration\Resolver\Result;

final class ConfigResolverResult implements \Countable
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @param bool $strict
     *
     * @return bool
     */
    public function isNull(bool $strict = true): bool
    {
        return $strict ? null === $this->value : null === $this->value;
    }

    /**
     * @param bool $strict
     *
     * @return bool
     */
    public function isTrue(bool $strict = true): bool
    {
        return (bool) ($strict ? true === $this->value : $this->value);
    }

    /**
     * @param bool $strict
     *
     * @return bool
     */
    public function isFalse(bool $strict = true): bool
    {
        return true !== $this->isTrue($strict);
    }

    /**
     * @return bool
     */
    public function isBoolean(): bool
    {
        return is_bool($this->value);
    }

    /**
     * @return bool
     */
    public function isString(): bool
    {
        return is_string($this->value);
    }

    /**
     * @return bool
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->value);
    }

    /**
     * @return bool
     */
    public function isInteger(): bool
    {
        return is_int($this->value);
    }

    /**
     * @return bool
     */
    public function isFloat(): bool
    {
        return is_float($this->value);
    }

    /**
     * @return bool
     */
    public function isScalar(): bool
    {
        return is_scalar($this->value);
    }

    /**
     * @return bool
     */
    public function isArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * @return bool
     */
    public function isObject(): bool
    {
        return is_object($this->value);
    }

    /**
     * @return float
     */
    public function count(): float
    {
        if ($this->isArray() || $this->isObject() && $this->get() instanceof \Countable) {
            return count($this->get());
        }

        if ($this->isNumeric()) {
            return $this->get();
        }

        return mb_strlen($this->toString());
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get($default = null)
    {
        return $this->value ?? $default;
    }

    /**
     * @return string
     */
    public function export(): string
    {
        return preg_replace('/([(,>])(\s?)\n\s*/', '$1 ', var_export($this->get(), true));
    }

    /**
     * @return bool|float|int|string|null
     */
    public function toScalar()
    {
        return $this->isNull() || $this->isScalar() ? $this->get() : $this->toString();
    }

    /**
     * @return bool
     */
    public function toString(): bool
    {
        return $this->invokeCastErrorRecoverable(function () {
            return (string) $this->get();
        }, function () {
            return $this->export();
        });
    }

    /**
     * @return bool
     */
    public function toBoolean(): bool
    {
        return $this->invokeCastErrorRecoverable(function () {
            return (bool) $this->get();
        });
    }

    /**
     * @return int|float|null
     */
    public function toNumeric()
    {
        return (null === $i = $this->toFloat()) || (null === $f = $this->toInteger())
            ? null
            : $i === (int) $f ? $i : $f;
    }

    /**
     * @return int|null
     */
    public function toInteger(): int
    {
        return $this->invokeCastErrorRecoverable(function () {
            return (int) $this->get();
        });
    }

    /**
     * @return float|null
     */
    public function toFloat(): ?float
    {
        return $this->invokeCastErrorRecoverable(function () {
            return (float) $this->get();
        });
    }

    /**
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->invokeCastErrorRecoverable(function () {
            return (array) $this->get();
        });
    }

    /**
     * @param \Closure      $act
     * @param \Closure|null $alt
     * @param mixed         ...$arguments
     *
     * @return mixed
     */
    private function invokeCastErrorRecoverable(\Closure $act, \Closure $alt = null, ...$arguments)
    {
        $this->setupCastErrorToExceptionHandler();

        try {
            return $act(...$arguments);
        } catch (\DomainException $exception) {
            return ($alt ?? function () {
                return null;
            })(...$arguments);
        } finally {
            $this->clearCastErrorToExceptionHandler();
        }
    }

    /**
     * @param int    $code
     * @param string $message
     * @param string $file
     * @param int    $line
     *
     * @return bool
     */
    private function callsCastErrorToExceptionHandler(int $code, string $message, string $file, int $line): bool
    {
        if ((error_reporting() & $code) && 1 === preg_match('/^.+could not be converted to [^\s]+$/', $message)) {
            throw new \DomainException($message);
        }

        return false;
    }

    private function setupCastErrorToExceptionHandler(): void
    {
        set_error_handler(function (int $c, string $m, string $f, int $l) {
            return $this->callsCastErrorToExceptionHandler($c, $m, $f, $l);
        }, E_RECOVERABLE_ERROR);
    }

    private function clearCastErrorToExceptionHandler(): void
    {
        restore_error_handler();
    }
}
