<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI;

class DateGenerator
{
    /**
     * Generate an array of dates
     * @param string $date last date
     * @param int $interval_in_days total interval in days
     * @param int $count required count of dates
     * @return array
     */
    public static function getDatesArray(string $date, int $interval_in_days, int $count = 7) : array
    {
        // return empty array for invalid input data
        if (!$interval_in_days || !$count) {
            return [];
        }

        // return no more than $interval_in_days dates
        if ($interval_in_days < $count) {
            $count = $interval_in_days;
        }

        $step_size = (int)($interval_in_days / $count);
        $dates = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $days = $i * $step_size;
            $dates[] = date('Y-m-d', strtotime($date . " -{$days} day"));
        }
        return $dates;
    }
}
