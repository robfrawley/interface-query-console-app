<?php

namespace App\Component\DotEnv;

use Symfony\Component\Dotenv\Dotenv as SymfonyDotEnv;

final class DotEnv
{
    /**
     * @var string[]
     */
    private $fileNames = [
        '.interface-query.env',
        '.env',
    ];

    /**
     * @var string[]
     */
    private $basePaths;

    /**
     * @param string ...$basePaths
     */
    public function __construct(string ...$basePaths)
    {
        $this->basePaths = array_filter(array_map(function (string $path) {
            return realpath($path) ?: null;
        }, $basePaths));
    }

    /**
     * @return SymfonyDotEnv|null
     */
    public function load(): ?SymfonyDotEnv
    {
        foreach ($this->basePaths as $basePath) {
            foreach ($this->fileNames as $fileName) {
                if (false !== $filePath = $this->createFilePath($basePath, $fileName)) {
                    try {
                        ($dotEnv = new SymfonyDotEnv())->load($filePath);

                        return $dotEnv;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $basePath
     * @param string $fileName
     *
     * @return string
     */
    private function createFilePath(string $basePath, string $fileName): string
    {
        return sprintf('%s/%s', realpath($basePath) ?: $basePath, $fileName);
    }
}
