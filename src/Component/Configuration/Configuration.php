<?php

/*
 * This file is part of the `src-run/interface-query-console-app` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Component\Configuration;

use App\Component\Configuration\Exception\IOException;
use App\Component\Configuration\Exception\LoadException;
use App\Component\Configuration\Exception\ParseException;
use App\Component\Filesystem\Path;
use App\Utility\Interpolation\PsrInterpolator;
use Ramsey\Uuid\Uuid;
use SR\Utilities\Interpreter\Interpreter;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

abstract class Configuration
{
    /**
     * @var string[]
     */
    protected const LOCATION_APP_CONFIG = [
        'application.yaml',
        '{self.conf}',
    ];

    /**
     * @var string[]
     */
    protected const MAP_NAME = [
        'name',
    ];

    /**
     * @var string[]
     */
    protected const MAP_CALL = [
        'call',
    ];

    /**
     * @var string[]
     */
    protected const MAP_DESC = [
        'desc',
    ];

    /**
     * @var string[]
     */
    private $roots = [];

    /**
     * @var string[]
     */
    private $files = [];

    /**
     * @var Path|null
     */
    private $path;

    /**
     * @var mixed[]
     */
    private $data = [];

    /**
     * @var string[]
     */
    private $namespace = [];

    /**
     * @param string|null $file
     * @param string      ...$roots
     */
    public function __construct(string $file = null, string ...$roots)
    {
        $this->addSearchFile($file);

        foreach ($roots as $path) {
            $this->addSearchRoot($path);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        try {
            return ($path = $this->getPath())
                ? $path->buildResolved()
                : Uuid::uuid5(Uuid::NAMESPACE_OID, spl_object_id($this))->toString();
        } catch (\Exception $e) {
            return Uuid::NIL;
        }
    }

    /**
     * @param string ...$indices
     *
     * @return self
     */
    public function setNamespace(string ...$indices): self
    {
        $this->namespace = $indices;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getNamespace(): array
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function hasCall(): bool
    {
        return $this->has(...self::MAP_CALL);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getCall(?string $default = 'undefined-application-calling-name'): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_CALL
        );
    }

    /**
     * @return bool
     */
    public function hasName(): bool
    {
        return $this->has(...self::MAP_NAME);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getName(?string $default = 'Undefined Application Display Name'): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_NAME
        );
    }

    /**
     * @return bool
     */
    public function hasDesc(): bool
    {
        return $this->has(...self::MAP_DESC);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getDesc(?string $default = 'Undefined Application Desc'): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_DESC
        );
    }

    /**
     * @param string $file
     *
     * @return self
     */
    public function addSearchFile(string $file): self
    {
        if (!in_array($file = self::interpolatePath($file), $this->files, true)) {
            $this->files[] = $file;
        }

        return $this;
    }

    /**
     * @param string $root
     *
     * @return self
     */
    public function addSearchRoot(string $root): self
    {
        if (!in_array($root = self::interpolatePath($root), $this->roots, true)) {
            $this->roots[] = $root;
        }

        return $this;
    }

    /**
     * @return Path|null
     */
    public function getPath(): ?Path
    {
        return $this->path;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string ...$indices
     *
     * @return bool
     */
    public function has(string ...$indices): bool
    {
        return null !== $this->get(...$indices);
    }

    /**
     * @param string ...$indices
     *
     * @return mixed|null
     */
    public function get(string ...$indices)
    {
        $indices = $this->namespaceIndices($indices);

        do {
            $v = ($v ?? $this->data)[array_shift($indices)] ?? null;
        } while (null !== $v && count($indices) > 0);

        return $v;
    }

    /**
     * @param callable|null $checker
     * @param string        ...$indices
     *
     * @return mixed|null
     */
    public function getIfValidOrUseNullVal(callable $checker = null, string ...$indices)
    {
        return $this->getIfValidOrUseDefault($checker, null, ...$indices);
    }

    /**
     * @param callable|null $checker
     * @param null          $default
     * @param string        ...$indices
     *
     * @return mixed|null
     */
    public function getIfValidOrUseDefault(callable $checker = null, $default = null, string ...$indices)
    {
        return ($checker ?? self::getDefaultValidator())($v = $this->get(...$indices)) ? $v : $default;
    }

    /**
     * @return self
     */
    public function load(): self
    {
        foreach ($this->roots as $root) {
            foreach ($this->files as $file) {
                if ($this->data = $this->loadFile(($this->path = new Path($root, $file))->resolve()->build())) {
                    break 2;
                }
            }
        }

        return $this;
    }

    /**
     * @return \Closure
     */
    protected static function useNonEmptyScalarChecker(): \Closure
    {
        return function ($value): bool {
            return is_scalar($value) && (!empty($value) || mb_strlen((string) $value) > 0);
        };
    }

    /**
     * @return \Closure
     */
    protected static function useNonEmptyArrayChecker(): \Closure
    {
        return function ($value): bool {
            return is_array($value) && !empty($value);
        };
    }

    /**
     * @return \Closure
     */
    protected static function usePositiveIntegerChecker(): \Closure
    {
        return function ($value): bool {
            return is_int($value) && $value >= 0;
        };
    }

    /**
     * @return \Closure
     */
    private static function getDefaultValidator(): \Closure
    {
        return function ($value): bool {
            return !empty($value);
        };
    }

    /**
     * @param array $indices
     *
     * @return array
     */
    private function namespaceIndices(array $indices): array
    {
        if (array_slice($indices, 0, count($this->namespace)) !== $this->namespace) {
            array_unshift($indices, ...$this->namespace);
        }

        return $indices;
    }

    /**
     * @param string $path
     *
     * @return array|null
     */
    private function loadFile(string $path): ?array
    {
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }

        try {
            return $this->readFileAndParseYaml($path);
        } catch (\Exception $exception) {
            throw new LoadException('Failed to load configuration file: "%s" (%s)', [
                $path, $exception->getMessage(),
            ], $exception);
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function readFileAndParseYaml(string $path): array
    {
        if (false === $content = @file_get_contents($path)) {
            throw new IOException('Failed to read file contents: "%s" (%s)', [
                $path, Interpreter::error(),
            ]);
        }

        try {
            return Yaml::parse(
                $content, Yaml::PARSE_CONSTANT | Yaml::PARSE_DATETIME | Yaml::PARSE_OBJECT
            );
        } catch (ParseException $exception) {
            throw new ParseException('Failed to parse file YAML: "%s" (%s)', [
                $path, $exception->getMessage(),
            ], $exception);
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private static function interpolatePath(string $path): string
    {
        return (new PsrInterpolator(
            $path, self::getPlaceholderReplacements()
        ))->compile();
    }

    /**
     * @return string[]
     */
    private static function getPlaceholderReplacements(): array
    {
        static $replacementMap;

        return $replacementMap ?? $replacementMap = array_filter([
            'self.work' => self::locateSelfWorkPath(),
            'self.root' => self::locateSelfRootPath(),
            'self.conf' => self::locateSelfConfPath(),
            'user.root' => self::locateUserHomePath(),
            'user.conf' => self::locateUserConfPath(),
            'host.conf' => self::locateHostConfPath(),
        ]);
    }

    /**
     * @return string
     */
    private static function locateSelfWorkPath(): string
    {
        if ((false !== $work = getcwd()) || (false !== $work = getenv('PWD'))) {
            return $work;
        }

        $process = new Process(['pwd']);
        $process->run();

        return 0 === $process->getExitCode()
            ? (new Path(trim($process->getOutput())))->buildResolved()
            : '';
    }

    /**
     * @return string
     */
    private static function locateSelfRootPath(): string
    {
        do {
            $last = $path ?? __DIR__;

            if (false !== realpath(implode(DIRECTORY_SEPARATOR, [$path ?? $last, 'vendor', '..', 'composer.json']))) {
                return (new Path($path ?? $last))->buildResolved();
            }
        } while ($last !== $path = dirname($last));

        return '';
    }

    /**
     * @return string|null
     */
    private static function locateSelfConfPath(): ?string
    {
        return (new Path(self::locateSelfRootPath(), 'src', 'Resources'))->buildResolved();
    }

    /**
     * @return string
     */
    private static function locateUserHomePath(): string
    {
        if (false !== $home = getenv('HOME')) {
            return (new Path($home))->buildResolved();
        }

        if (false !== $home = (posix_getpwuid(posix_geteuid())['dir'] ?? false)) {
            return (new Path($home))->buildResolved();
        }

        if (false !== $name = (posix_getpwuid(posix_geteuid())['name'] ?? false)) {
            return (new Path('/home', $name))->buildResolved();
        }

        return '';
    }

    /**
     * @return string
     */
    private static function locateUserConfPath(): string
    {
        return (new Path(
            self::locateUserHomePath(), '.config', 'interface-query'
        ))->buildResolved();
    }

    /**
     * @return string
     */
    private static function locateHostConfPath(): string
    {
        return (new Path('/etc'))->buildResolved();
    }

    /**
     * @param string ...$components
     *
     * @return Path
     */
    private static function createPath(string ...$components): Path
    {
        return new Path(...$components);
    }
}
