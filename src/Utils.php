<?php

namespace ConventionalChangelog;

use DateTime;

class Utils
{
    /**
     * Clean string removing double spaces and trim.
     *
     * @param $string
     *
     * @return string
     */
    public static function clean($string)
    {
        $string = trim($string);

        return preg_replace('/[ ]+/m', ' ', $string);
    }

    /**
     *  Get today date string formatted.
     *
     * @param DateTime $today
     *
     * @return string
     */
    public static function getDateString($date)
    {
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
        $day = date('j', $date->getTimestamp());
        $month = date('n', $date->getTimestamp());
        $monthName = $months[$month - 1];
        $year = date('Y', $date->getTimestamp());

        return $day . ' ' . $monthName . ' ' . $year;
    }

    /**
     * Bump semantic version.
     *
     * @param $version
     * @param false $major
     * @param false $minor
     * @param bool $patch
     *
     * @return string
     */
    public static function bumpVersion($version, $major = false, $minor = false, $patch = false)
    {
        $newVersion = [0, 0, 0];
        $increaseKeys = [];

        $version = preg_replace('#^v#i', '', $version);

        // Generate new version code
        $parts = explode('.', $version);

        foreach ($parts as $key => $value) {
            $newVersion[$key] = (int)$value;
        }

        // Increase major
        if ($major) {
            $increaseKeys[] = 0;
        }

        // Increase minor
        if ($minor) {
            $increaseKeys[] = 1;
        }

        // Increase patch
        if ($patch || (!$major && !$minor && !$patch)) {
            $increaseKeys[] = 2;
        }

        foreach ($increaseKeys as $key) {
            $newVersion[$key]++;
        }

        // Recompose semver
        return implode('.', $newVersion);
    }
}
