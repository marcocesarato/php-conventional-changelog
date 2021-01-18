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
     *
     * @param string $mode
     */
    public function bump($mode): string
    {
        $version = $this->getVersion();

        $newVersion = [0, 0, 0];
        $version = preg_replace('#^v#i', '', $version);

        // Generate new version code
        $split = explode('-', $version);
        $extra = !empty($split[1]) ? $split[1] : '';
        $parts = explode('.', $split[0]);

        foreach ($parts as $key => $value) {
            $newVersion[$key] = (int)$value;
        }

        $extraModes = [self::RELEASE_RC, self::RELEASE_BETA, self::RELEASE_ALPHA];

        if (in_array($mode, $extraModes)) {
            $partsExtra = explode('.', $extra);
            $extraName = $partsExtra[0];
            $extraVersion = !empty($partsExtra[1]) ? $partsExtra[1] : 0;
            if (is_numeric($extraName) && (empty($partsExtra[1]) || !is_numeric($partsExtra[1]))) {
                $extraVersion = $partsExtra[0];
            } elseif ($extraName !== $mode) {
                $extraVersion = 0;
            }
            $extraVersion++;
            $extra = "{$mode}.{$extraVersion}";
        }

        if ($mode === self::RELEASE_MAJOR) {
            $newVersion[0]++;
        } elseif ($mode === self::RELEASE_MINOR) {
            $newVersion[1]++;
        } elseif ($mode === self::RELEASE_PATCH) {
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
