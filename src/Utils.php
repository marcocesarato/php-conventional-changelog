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
}
