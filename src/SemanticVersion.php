<?php

namespace ConventionalChangelog;

class SemanticVersion
{
    public const RELEASE_MAJOR = 'major';
    public const RELEASE_MINOR = 'minor';
    public const RELEASE_PATCH = 'patch';
    public const RELEASE_RC = 'rc';
    public const RELEASE_BETA = 'beta';
    public const RELEASE_ALPHA = 'alpha';

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
        $this->setVersion($version);
    }

    /**
     * Bump version.
     */
    public function bump(string $release): string
    {
        $version = $this->getVersion();

        $newVersion = [0, 0, 0];
        $version = preg_replace('#^v#i', '', $version);

        // Generate new version code
        $split = explode('-', $version, 2);
        $extra = !empty($split[1]) ? $split[1] : '';

        $extraReleases = [self::RELEASE_RC, self::RELEASE_BETA, self::RELEASE_ALPHA];

        if (in_array($release, $extraReleases)) {
            $partsExtra = explode('.', $extra);
            $extraName = $partsExtra[0];
            $extraVersion = !empty($partsExtra[1]) ? $partsExtra[1] : 0;
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

        if ($release === self::RELEASE_MAJOR) {
            $newVersion[0]++;
        } elseif ($release === self::RELEASE_MINOR) {
            $newVersion[1]++;
        } elseif ($release === self::RELEASE_PATCH) {
            $newVersion[2]++;
        }

        // Recompose semver
        $version = implode('.', $newVersion) . (!empty($extra) ? '-' . $extra : '');

        $this->setVersion($version);

        return $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): SemanticVersion
    {
        $this->version = $version;

        return $this;
    }
}
