<?php

namespace ConventionalChangelog\Helper;

class CalendarVersion
{
    public const DEFAULT_FORMAT = 'YYYY.MM.DD.PATCH';

    /**
     * Generate the next calendar version from existing version strings.
     */
    public static function generate(array $versions, string $format = self::DEFAULT_FORMAT, ?\DateTimeInterface $date = null): string
    {
        if (substr_count($format, 'PATCH') !== 1) {
            throw new \InvalidArgumentException('The CalVer format must contain PATCH exactly once.');
        }

        $date = $date ?: new \DateTimeImmutable();
        $version = strtr($format, [
            'YYYY' => $date->format('Y'),
            'YY' => $date->format('y'),
            'MM' => $date->format('m'),
            'DD' => $date->format('d'),
        ]);
        $pattern = '/^' . str_replace('PATCH', '(?<patch>[0-9]+)', preg_quote($version, '/')) . '$/';
        $patch = -1;

        foreach ($versions as $existingVersion) {
            if (preg_match($pattern, $existingVersion, $matches)) {
                $patch = max($patch, (int)$matches['patch']);
            }
        }

        return str_replace('PATCH', (string)($patch + 1), $version);
    }
}
