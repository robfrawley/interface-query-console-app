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

final class AppConfiguration extends Configuration
{
    /**
     * @var string[]
     */
    private const MAP_AUTHOR_NAME = [
        'author', 'name',
    ];

    /**
     * @var string[]
     */
    private const MAP_AUTHOR_MAIL = [
        'author', 'mail',
    ];

    /**
     * @var string[]
     */
    private const MAP_AUTHOR_LINK = [
        'author', 'link',
    ];

    /**
     * @var string[]
     */
    private const MAP_LICENSE_NAME = [
        'license', 'name',
    ];

    /**
     * @var string[]
     */
    private const MAP_LICENSE_LINK = [
        'license', 'link',
    ];

    /**
     * @var string[]
     */
    private const MAP_VERSION_MAJOR = [
        'version', 'major',
    ];

    /**
     * @var string[]
     */
    private const MAP_VERSION_MINOR = [
        'version', 'minor',
    ];

    /**
     * @var string[]
     */
    private const MAP_VERSION_PATCH = [
        'version', 'patch',
    ];

    /**
     * @var string[]
     */
    private const MAP_VERSION_EXTRA = [
        'version', 'extra',
    ];

    /**
     * @var string[]
     */
    private const MAP_VERSION_NAMED = [
        'version', 'named',
    ];

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
        return $this->getValidOrDefault(
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
        return $this->getValidOrDefault(
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
        return $this->getValidOrDefault(
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
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_VERSION_EXTRA
        );
    }

    /**
     * @return bool
     */
    public function hasVersionNamed(): bool
    {
        return $this->has(...self::MAP_VERSION_NAMED);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getVersionNamed(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_VERSION_NAMED
        );
    }

    /**
     * @param string|null $formatVersion
     * @param bool        $formatExtra
     * @param bool        $formatNamed
     *
     * @return string
     */
    public function stringifyVersion(string $formatVersion = null, bool $formatExtra = true, bool $formatNamed = false): string
    {
        $formats = [$formatVersion ?? '%d.%d.%d'];
        $replace = [$this->getVersionMajor(), $this->getVersionMinor(), $this->getVersionPatch()];

        if ($this->hasVersionExtra() && $formatExtra) {
            $replace[] = $this->getVersionExtra();
            $formats[] = '-%s';
        }

        if ($this->hasVersionNamed() && $formatNamed) {
            $replace[] = $this->getVersionNamed();
            $formats[] = ' (%s)';
        }

        return vsprintf(implode('', $formats), $replace);
    }

    /**
     * @return bool
     */
    public function hasAuthorName(): bool
    {
        return $this->has(...self::MAP_AUTHOR_NAME);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getAuthorName(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_AUTHOR_NAME
        );
    }

    /**
     * @return bool
     */
    public function hasAuthorMail(): bool
    {
        return $this->has(...self::MAP_AUTHOR_MAIL);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getAuthorMail(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_AUTHOR_MAIL
        );
    }

    /**
     * @return bool
     */
    public function hasAuthorLink(): bool
    {
        return $this->has(...self::MAP_AUTHOR_LINK);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getAuthorLink(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_AUTHOR_LINK
        );
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function stringifyAuthor(string $format = '%s <%s> (%s)'): string
    {
        return vsprintf($format, [
            $this->getAuthorName(),
            $this->getAuthorMail(),
            $this->getAuthorLink(),
        ]);
    }

    /**
     * @return bool
     */
    public function hasLicenseName(): bool
    {
        return $this->has(...self::MAP_LICENSE_NAME);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getLicenseName(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_LICENSE_NAME
        );
    }

    /**
     * @return bool
     */
    public function hasLicenseLink(): bool
    {
        return $this->has(...self::MAP_LICENSE_LINK);
    }

    /**
     * @param string|null $default
     *
     * @return string|null
     */
    public function getLicenseLink(?string $default = null): ?string
    {
        return $this->getValidOrDefault(
            self::useNonEmptyScalarChecker(), $default, ...self::MAP_LICENSE_LINK
        );
    }

    /**
     * @param string $format
     *
     * @return string
     */
    public function stringifyLicense(string $format = '%s (%s)'): string
    {
        return vsprintf($format, [
            $this->getLicenseName(),
            $this->getLicenseLink(),
        ]);
    }

    /**
     * @param string ...$namespace
     *
     * @return string[]
     */
    protected function resolveNamespace(string ...$namespace): array
    {
        return empty($namespace) ? ['application'] : $namespace;
    }
}
