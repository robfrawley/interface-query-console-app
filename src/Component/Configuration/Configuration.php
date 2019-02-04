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

use App\Component\Configuration\Exception\CacheException;
use App\Component\Configuration\Exception\IOException;
use App\Component\Configuration\Exception\LoadException;
use App\Component\Configuration\Exception\ParseException;
use App\Component\Filesystem\Path;
use App\Utility\Interpolation\PsrInterpolator;
use Ramsey\Uuid\Uuid;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Utilities\Interpreter\Interpreter;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

abstract class Configuration
{
    /**
     * @var string
     */
    protected const LOCATION_CONFIG_FILE = 'application.yaml';

    /**
     * @var string[]
     */
    protected const LOCATION_CONFIG_DIRS = [
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
     * @var array[]
     */
    private static $cachedConfigs = [];

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
     * @param array|null  $roots
     * @param string      ...$namespace
     */
    public function __construct(string $file = null, array $roots = null, string ...$namespace)
    {
        $this->addSearchFile(
            $file ?? self::LOCATION_CONFIG_FILE
        );

        foreach ($roots ?? self::LOCATION_CONFIG_DIRS as $path) {
            $this->addSearchRoot($path);
        }

        $this->setNamespace(...self::prepareNamespace(...$namespace));
        $this->load();
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
    public function getCall(?string $default = 'undefined'): ?string
    {
        return $this->getValidOrDefault(
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
    public function getName(?string $default = 'Undefined'): ?string
    {
        return $this->getValidOrDefault(
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
    public function getDesc(?string $default = 'Undefined'): ?string
    {
        return $this->getValidOrDefault(
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
     * @param callable|null $validator
     * @param null          $default
     * @param string        ...$indices
     *
     * @return mixed|null
     */
    public function getValidOrDefault(callable $validator = null, $default = null, string ...$indices)
    {
        return ($validator ?? self::getDefaultValidator())(
            $v = $this->get(...$indices)
        ) ? $v : $default;
    }

    /**
     * @return self
     */
    public function load(): self
    {
        foreach ($this->roots as $root) {
            foreach ($this->files as $file) {
                if ($this->data = $this->loadConfig(($this->path = self::createPath($root, $file))->resolve()->build())) {
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
    protected static function useBooleanTypeChecker(): \Closure
    {
        return function ($value): bool {
            return is_bool($value);
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
     * @param string ...$namespace
     *
     * @return string[]
     */
    private function prepareNamespace(string ...$namespace): array
    {
        $callable = [$this, 'resolveNamespace'];

        if (method_exists(...$callable) && is_callable($callable)) {
            $namespace = call_user_func($callable, ...$namespace);
        }

        if (empty($namespace)) {
            throw new InvalidArgumentException(
                'Required namespace context not provided during construction.'
            );
        }

        return $namespace;
    }

    /**
     * @param string $path
     *
     * @return array|null
     */
    private function loadConfig(string $path): ?array
    {
        try {
            return self::loadConfigAsCache($path) ?? self::saveConfigToCache(
                $path,
                $this->loadConfigParsedAsYAML(
                    $this->readConfigFileContents($path), $path
                )
            );
        } catch (\Exception $e) {
            throw new LoadException(
                'Failed to load config file: "%s" (%s)', [$path, $e->getMessage()], $e
            );
        }
    }

    /**
     * @param string $path
     *
     * @return string|null
     */
    private function readConfigFileContents(string $path): ?string
    {
        if ((true !== $exists = file_exists($path)) || (true !== is_readable($path))) {
            throw new IOException('Failed to locate file: "%s" (%s)', [
                $path, (false === $exists ? 'file does not exist' : 'file is not readable'),
            ]);
        }

        if (false === $content = @file_get_contents($path)) {
            throw new IOException(
                'Failed to get file contents: "%s" (%s)', [$path, Interpreter::error()]
            );
        }

        return $content;
    }

    /**
     * @param string $text
     * @param string $path
     *
     * @return array
     */
    private function loadConfigParsedAsYAML(string $text, string $path): array
    {
        try {
            $config = Yaml::parse(
                $text, Yaml::PARSE_CONSTANT | Yaml::PARSE_DATETIME | Yaml::PARSE_OBJECT
            );
        } catch (ParseException $e) {
            throw new ParseException(
                'Failed to parse file: "%s" (%s)', [$path, $e->getMessage()], $e
            );
        }

        if (empty($config)) {
            throw new ParseException(
                'Failed to load config file: "%s" (parsed file to empty array)', [$path]
            );
        }

        return $config;
    }

    /**
     * @param string $path
     *
     * @return array|null
     */
    private static function loadConfigAsCache(string $path): ?array
    {
        return self::$cachedConfigs[self::makeConfigCacheKey($path)] ?? null;
    }

    /**
     * @param string     $path
     * @param array|null $config
     *
     * @return array|null
     */
    private static function saveConfigToCache(string $path, ?array $config = []): ?array
    {
        return self::$cachedConfigs[self::makeConfigCacheKey($path)] = $config;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private static function makeConfigCacheKey(string $path): string
    {
        static $algorithm;

        return hash($algorithm ?? $algorithm = self::decideBestAvailHashAlgo(), $path);
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private static function decideBestAvailHashAlgo(): string
    {
        $algos = ['sha3-512', 'sha3-256', 'sha512', 'sha512/256', 'sha256', 'sha1', 'md5'];
        $found = array_filter($algos, function (string $name): bool {
            return in_array($name, hash_algos(), true);
        });

        if (empty($found)) {
            throw new CacheException(
                'Failed to decide best available hash algorithm of: %s (none supported by host system).',
                self::implodeQuotedList($algos)
            );
        }

        return array_shift($found);
    }

    /**
     * @param array $list
     *
     * @return string
     */
    private static function implodeQuotedList(array $list): string
    {
        return implode(', ', array_map(function (string $name): string {
            return sprintf('"%s"', $name);
        }, $list));
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
            ? self::createPath(trim($process->getOutput()))->buildResolved()
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
                return self::createPath($path ?? $last)->buildResolved();
            }
        } while ($last !== $path = dirname($last));

        return '';
    }

    /**
     * @return string|null
     */
    private static function locateSelfConfPath(): ?string
    {
        return self::createPath(self::locateSelfRootPath(), 'src', 'Resources')->buildResolved();
    }

    /**
     * @return string
     */
    private static function locateUserHomePath(): string
    {
        if (false !== $home = getenv('HOME')) {
            return self::createPath($home)->buildResolved();
        }

        if (false !== $home = (posix_getpwuid(posix_geteuid())['dir'] ?? false)) {
            return self::createPath($home)->buildResolved();
        }

        if (false !== $name = (posix_getpwuid(posix_geteuid())['name'] ?? false)) {
            return self::createPath('/home', $name)->buildResolved();
        }

        return '';
    }

    /**
     * @return string
     */
    private static function locateUserConfPath(): string
    {
        return self::createPath(self::locateUserHomePath(), '.config', 'interface-query')->buildResolved();
    }

    /**
     * @return string
     */
    private static function locateHostConfPath(): string
    {
        return self::createPath('/etc')->buildResolved();
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

    /**
     * @return \Closure
     */
    private static function getDefaultValidator(): \Closure
    {
        return function ($value): bool {
            return !empty($value);
        };
    }
}
