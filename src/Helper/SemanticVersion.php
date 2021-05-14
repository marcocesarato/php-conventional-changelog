<?php

namespace ConventionalChangelog\Helper;

class SemanticVersion
{
    /**
     * Pattern to detect semver.
     *
     * @var string
     */
    public const PATTERN = '([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?';

    /**
     * Pattern no extra.
     *
     * @var string
     */
    public const PATTERN_NO_EXTRA = '([0-9]+)\.([0-9]+)\.([0-9]+)';

    /**
     * @var string
     */
    public const MAJOR = 'major';
    /**
     * @var string
     */
    public const MINOR = 'minor';
    /**
     * @var string
     */
    public const PATCH = 'patch';
    /**
     * @var string
     */
    public const RC = 'rc';
    /**
     * @var string
     */
    public const BETA = 'beta';
    /**
     * @var string
     */
    public const ALPHA = 'alpha';

    /**
     * @var string
     */
    protected $version;

    /**
     * Constructor.
     *
     * @param $version
     */
    public function __construct($version)
    {
        $version = trim($version);
        $version = preg_replace('#^v#i', '', $version);

        $this->setVersion($version);
    }

    /**
     * Bump version.
     */
    public function bump(string $release): string
    {
        $version = $this->getVersion();

        $newVersion = [0, 0, 0];

        if (!preg_match('/^' . self::PATTERN . '$/', $version)) {
            return $version;
        }

        // Generate new version code
        $split = explode('-', $version, 2);
        $extra = empty($split[1]) ? '' : $split[1];

        $extraReleases = [self::RC, self::BETA, self::ALPHA];

        if (in_array($release, $extraReleases)) {
            $partsExtra = explode('.', $extra);
            $extraName = $partsExtra[0];
            $extraVersion = empty($partsExtra[1]) ? 0 : $partsExtra[1];
            if (is_numeric($extraName) && (empty($partsExtra[1]) || !is_numeric($partsExtra[1]))) {
                $extraVersion = $partsExtra[0];
            } elseif ($extraName !== $release) {
                $extraVersion = 0;
            }
            $extraVersion++;
            $extra = "{$release}.{$extraVersion}";
        }

        $parts = explode('.', $split[0]);
        foreach ($parts as $key => $value) {
            $newVersion[$key] = (int)$value;
        }

        if ($release === self::MAJOR) {
            $newVersion[0]++;
            $newVersion[1] = 0;
            $newVersion[2] = 0;
        } elseif ($release === self::MINOR) {
            $newVersion[1]++;
            $newVersion[2] = 0;
        } elseif ($release === self::PATCH) {
            $newVersion[2]++;
        }

        // Recompose semver
        $version = implode('.', $newVersion) . (empty($extra) ? '' : '-' . $extra);
        $this->setVersion($version);

        return $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getVersionCode(): string
    {
        if (preg_match('/' . self::PATTERN_NO_EXTRA . '/', $this->version, $match)) {
            return $match[0];
        }

        return '0.0.0';
    }

    public function setVersion(string $version): SemanticVersion
    {
        $this->version = $version;

        return $this;
    }
}
