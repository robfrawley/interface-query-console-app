<?php

namespace App\Component\Configuration;

final class AppConfiguration extends Configuration
{
    /**
     * @param string|null $context
     */
    public function __construct(string $context = null)
    {
        parent::__construct(
            ...self::LOCATION_APP_CONFIG
        );

        $this->load();
        $this->setNamespace($context ?? 'application');
    }

    /**
     * @return bool
     */
    public function hasVersionMajor(): bool
    {
        return $this->has(...self::MAP_VERSION_MAJOR);
    }

    /**
     * @param int|null $default
     *
     * @return string|null
     */
    public function getVersionMajor(?int $default = 0): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::usePositiveIntegerChecker(), $default, ...self::MAP_VERSION_MAJOR
        );
    }

    /**
     * @return bool
     */
    public function hasVersionMinor(): bool
    {
        return $this->has(...self::MAP_VERSION_MINOR);
    }

    /**
     * @param int|null $default
     *
     * @return string|null
     */
    public function getVersionMinor(?int $default = 0): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::usePositiveIntegerChecker(), $default, ...self::MAP_VERSION_MINOR
        );
    }

    /**
     * @return bool
     */
    public function hasVersionPatch(): bool
    {
        return $this->has(...self::MAP_VERSION_PATCH);
    }

    /**
     * @param int|null $default
     *
     * @return string|null
     */
    public function getVersionPatch(?int $default = 0): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::usePositiveIntegerChecker(), $default, ...self::MAP_VERSION_PATCH
        );
    }

    /**
     * @return bool
     */
    public function hasVersionExtra(): bool
    {
        return $this->has(...self::MAP_VERSION_EXTRA);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getVersionExtra(?string $default = null): ?string
    {
        return $this->getIfValidOrUseDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_VERSION_EXTRA
        );
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function stringifyVersion(string $format = '%d.%d.%d-%s'): string
    {
        $replacements = [
            $this->getVersionMajor(),
            $this->getVersionMinor(),
            $this->getVersionPatch(),
            $this->getVersionExtra(),
        ];

        if (!$this->hasVersionExtra()) {
            $format = preg_replace('/-%s$/', '', $format);
            array_pop($replacements);
        }

        return vsprintf($format, $replacements);
    }
}
