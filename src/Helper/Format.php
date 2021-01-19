<?php

namespace ConventionalChangelog\Helper;

use DateTime;

class Format
{
    /**
     * Clean string removing double spaces and trim.
     */
    public static function clean(string $string): string
    {
        $string = trim($string);

        return preg_replace('/[[:blank:]]+/m', ' ', $string);
    }

    /**
     *  Get today date string formatted.
     *
     * @param DateTime $today
     */
    public static function getDateString(DateTime $date): string
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
}
