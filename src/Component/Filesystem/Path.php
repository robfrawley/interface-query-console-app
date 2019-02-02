<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Filesystem;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Path implements \Countable, \IteratorAggregate
{
    /**
     * @var string[]
     */
    private $components;

    /**
     * @param string ...$components
     */
    public function __construct(string ...$components)
    {
        $this->components = self::normalizeComponents($components);
    }

    /**
     * @return UuidInterface
     */
    public function uuid(): UuidInterface
    {
        return Uuid::uuid3(Uuid::NAMESPACE_URL, $this->buildUri());
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->components);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->components);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    /**
     * @return bool
     */
    public function isExisting(): bool
    {
        return file_exists($this->build());
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        return is_readable($this->build());
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return is_writable($this->build());
    }

    /**
     * @return bool
     */
    public function isFileType(): bool
    {
        return $this->isExisting() && is_file($this->build());
    }

    /**
     * @return bool
     */
    public function isPathType(): bool
    {
        return $this->isExisting() && is_dir($this->build());
    }

    /**
     * @return bool
     */
    public function isAbsolute(): bool
    {
        return DIRECTORY_SEPARATOR === $this->getComponentAtIndex(0);
    }

    /**
     * @return bool
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * @return self
     */
    public function makeAbsolute(): self
    {
        if (!$this->isAbsolute()) {
            $this->prepend(DIRECTORY_SEPARATOR);
        }

        return $this;
    }

    /**
     * @return self
     */
    public function makeRelative(): self
    {
        if ($this->isAbsolute()) {
            $this->shift();
        }

        return $this;
    }

    /**
     * @return self
     */
    public function resolve(): self
    {
        $this->components = self::normalizeComponents([
            realpath($path = $this->build()) ?: $path,
        ]);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getComponentListing(): array
    {
        return $this->components;
    }

    /**
     * @param int $index
     *
     * @return string|null
     */
    public function getComponentAtIndex(int $index): ?string
    {
        return $this->components[$index] ?? null;
    }

    /**
     * @param string ...$components
     *
     * @return self
     */
    public function prepend(string ...$components): self
    {
        array_unshift(
            $this->components, ...self::normalizeComponents($components)
        );

        return $this;
    }

    /**
     * @param string ...$components
     *
     * @return self
     */
    public function append(string ...$components): self
    {
        array_push(
            $this->components, ...self::normalizeComponents($components)
        );

        return $this;
    }

    /**
     * @return string[]
     */
    public function shift(): ?string
    {
        return array_shift($this->components);
    }

    /**
     * @param int $count
     *
     * @return string[]
     */
    public function shiftMultiple(int $count = 1): array
    {
        if (!$this->isEmpty() && $count > 0) {
            $this->makeRelative();

            $list = array_map(function () {
                return $this->shift();
            }, range(1, min($count, $this->count())));
        }

        return $list ?? [];
    }

    /**
     * @return string|null
     */
    public function pop(): ?string
    {
        return array_pop($this->components);
    }

    /**
     * @param int $count
     *
     * @return string[]
     */
    public function popMultiple(int $count = 1): array
    {
        return $this->isEmpty() || $count <= 0 ? [] : array_map(function () {
            return $this->pop();
        }, range(1, min($count, $this->count())));
    }

    /**
     * @return string
     */
    public function build(): string
    {
        return self::normalizeSeparators(implode(DIRECTORY_SEPARATOR, $this->components));
    }

    /**
     * @return string
     */
    public function buildUri(): string
    {
        return sprintf('file://%s', $this->buildAbsolute());
    }

    /**
     * @return string
     */
    public function buildResolved(): string
    {
        return (clone $this)->resolve()->build();
    }

    /**
     * @return string
     */
    public function buildRelative(): string
    {
        return (clone $this)->makeRelative()->build();
    }

    /**
     * @return string
     */
    public function buildAbsolute(): string
    {
        return (clone $this)->makeAbsolute()->build();
    }

    /**
     * @param array $components
     *
     * @return array
     */
    private static function normalizeComponents(array $components): array
    {
        $normalized = [];

        foreach ($components as $i => $v) {
            $normalized = array_merge(
                $normalized,
                explode('/', self::normalizeSeparators($v))
            );
        }

        if (0 === mb_strpos(self::normalizeSeparators($components[0] ?? ''), DIRECTORY_SEPARATOR)) {
            array_unshift($normalized, DIRECTORY_SEPARATOR);
        }

        return array_values(array_filter(array_merge([
            $normalized[0] ?? null,
        ], array_filter(array_slice($normalized, 1), function (string $v): bool {
            return DIRECTORY_SEPARATOR !== $v;
        }))));
    }

    /**
     * @param string $provided
     *
     * @return string
     */
    private static function normalizeSeparators(string $provided): string
    {
        return preg_replace('{[/\\\]+}', DIRECTORY_SEPARATOR, $provided);
    }
}
