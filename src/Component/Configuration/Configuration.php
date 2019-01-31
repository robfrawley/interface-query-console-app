<?php

namespace App\Component\Configuration;

use App\Component\Configuration\Exception\IOException;
use App\Component\Configuration\Exception\LoadException;
use App\Component\Configuration\Exception\ParseException;
use App\Component\Filesystem\Path;
use App\Utility\Interpolation\PsrInterpolator;
use SR\Utilities\Interpreter\Interpreter;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

abstract class Configuration
{
    /**
     * @var string[]
     */
    private $roots = [];

    /**
     * @var string[]
     */
    private $files = [];

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var mixed[]
     */
    private $data = [];

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
     * @param string $file
     *
     * @return self
     */
    public function addSearchFile(string $file): self
    {
        if (!in_array($file = self::interpolatePath($file), $this->files)) {
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
        if (!in_array($root = self::interpolatePath($root), $this->roots)) {
            $this->roots[] = $root;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
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
        do {
            $data = ($data ?? $this->data)[array_shift($indices)] ?? null;
        } while (null !== $data && count($indices) > 0);

        return $data;
    }

    /**
     * @return self
     */
    public function load(): self
    {
        foreach ($this->roots as $root) {
            foreach ($this->files as $file) {
                if ($this->data = $this->loadFile((new Path($root, $file))->buildResolved())) {
                    break 2;
                }
            }
        }

        return $this;
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
