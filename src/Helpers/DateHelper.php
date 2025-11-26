<?php

namespace Knighttower\Toolbox\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class DateHelper
{
    // ==================================
    // UTILITY FUNCTIONS
    // -------------------------

    /**
     * Create a list of years for the app since 2016
     *
     * @param int $year The year it should start from
     * @return object
     */
    public static function getYears(int $year = 2016): object
    {
        $diff = Carbon::createFromDate($year, 1, 1)->diff(Carbon::now())->format('%y');
        $years = [
            'ALL' => '2000-01-01'
        ];
        for ($i = 0; $i < $diff; $i++) {
            $year = Carbon::now()->startOfYear()->subYears($i);
            $yearValue = $year->format('Y-m-d');
            $yearLabel = $year->format('Y');

            $years[$yearLabel] = $yearValue;
        }

        return (object) Collection::make($years)->sortKeysDesc()->all();
    }

    /**
     * Find the type of Format used
     *
     * @param string $date
     * @return string|null
     */
    public static function formatType(string $date): ?string
    {
        $patterns = [
            'm-d-Y' => '/^\d{2}-\d{2}-\d{4}/',
            'Y-m-d' => '/^\d{4}-\d{2}-\d{2}/',
            'm/d/Y' => '/^\d{1,2}\/\d{1,2}\/\d{4}/',
            'm/d/y' => '/^\d{1,2}\/\d{1,2}\/\d{2}/',
            'Y/m/d' => '/^\d{4}\/\d{2}\/\d{2}/',
        ];
        foreach ($patterns as $format => $pattern) {
            if (!empty(preg_match($pattern, $date, $match))) {
                return $format;
            }
        }

        return null;
    }


    /**
     * Check or create a Carbon instance
     *
     * @param string|object $date
     * @return bool
     */
    private static function isCarbonInstance($date): bool
    {
        return (bool) ($date instanceof Illuminate\Support\Carbon || $date instanceof \Carbon\Carbon);
    }


    /**
     * Detect a date by format
     *
     * @param string $date
     * @return boolean
     */
    public static function isDate($date): bool
    {
        if (self::isCarbonInstance($date)) {
            return true;
        }

        if (self::formatType($date)) {
            foreach (self::getYears() as $key => $year) {
                $cases = [
                    '-' . $key,
                    $key . '-',
                    '/' . $key,
                    $key . '/',
                    '/' . substr($key, -2),
                ];
                if (Str::contains($date, $cases)) {
                    return true;
                }
            }
        }

        return false;
    }


    // ================================
    // DATE MANIPULATION
    // ----------------------

    /**
     * Check or create a Carbon instance
     *
     * @param string|object $date
     * @return Carbon
     */
    public static function toCarbon($date): Carbon
    {
        if (self::isCarbonInstance($date)) {
            return $date;
        }
        $format = self::formatType($date);

        if ($format && $format !== 'Y-m-d') {
            $date = Carbon::createFromFormat($format, $date)->format('Y-m-d');
        }

        return Carbon::parse($date);
    }

    /**
     * Standard date format
     *
     * @param String|Object $date
     * @return String
     */
    public static function date($date)
    {
        return self::toCarbon($date)->format('m-d-Y');
    }

    /**
     * Standard date format
     *
     * @param String|Object $date
     * @return String
     */
    public static function dateExcel($date)
    {
        return self::toCarbon($date)->format('m/d/Y');
    }

    /**
     * Standard date & time format
     *
     * @param String|Object $date
     * @return String
     */
    public static function dateTime($date)
    {
        return self::toCarbon($date)->format('m-d-Y @ g:i A');
    }

    /**
     * Standard date format for Unix or Dbs
     *
     * @param String|Object $date
     * @return String
     */
    public static function dateUnix($date)
    {
        return self::toCarbon($date)->format('Y-m-d');
    }

    /**
     * Convert a Carbon instance or a string into date format Y-m-d H:i:s string.
     *
     * @param string|Carbon $date
     * @return string
     */
    public static function toDbDateTime($date): string
    {
        return self::toCarbon($date)->format('Y-m-d H:i:s');
    }
}