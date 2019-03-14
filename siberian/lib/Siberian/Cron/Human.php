<?php

namespace Siberian\Cron;

/**
 * Class Human
 * @package Siberian\Cron
 */
/**
 * Class Human
 * @package Siberian\Cron
 */
class Human
{
    /**
     * @var array
     */
    private $_minutes = [];
    /**
     * @var array
     */
    private $_hours = [];
    /**
     * @var array
     */
    private $_daysOfMonth = [];
    /**
     * @var array
     */
    private $_months = [];
    /**
     * @var array
     */
    private $_daysOfWeek = [];
    /**
     * @var array
     */
    private $_years = [];

    /**
     * @var array
     */
    private $_cronMinutes = [];
    /**
     * @var array
     */
    private $_cronHours = [];
    /**
     * @var array
     */
    private $_cronDaysOfMonth = [];
    /**
     * @var array
     */
    private $_cronMonths = [];
    /**
     * @var array
     */
    private $_cronDaysOfWeek = [];
    /**
     * @var array
     */
    private $_cronYears = [];

    /**
     * @var bool
     */
    private $_lang = FALSE;

    /**
     * @var int
     */
    protected $RANGE_YEARS_MIN = 1970;    // Must match date range supported by date(). See also: http://en.wikipedia.org/wiki/Year_2038_problem
    /**
     * @var int
     */
    protected $RANGE_YEARS_MAX = 2037;    // Must match date range supported by date(). See also: http://en.wikipedia.org/wiki/Year_2038_problem

    /**
     * Human constructor.
     * @param string $language
     */
    public function __construct($language = 'en')
    {
        $this->initLang($language);
    }

    /**
     * @param string $cronSpec
     * @param string $language
     * @return Human
     * @throws \Exception
     */
    final public static function fromCronString($cronSpec = '* * * * * *', $language = 'en')
    {
        // Split input liberal. Single or multiple Spaces, Tabs and Newlines are all allowed as separators.
        if (count($elements = preg_split('/\s+/', $cronSpec)) < 5) {
            throw new \Exception('Invalid specification.');
        }

        $arrMonths = ['JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12];
        $arrDaysOfWeek = ['SUN' => 0, 'MON' => 1, 'TUE' => 2, 'WED' => 3, 'THU' => 4, 'FRI' => 5, 'SAT' => 6];

        // Translate the cron specification into arrays that hold specifications of the actual dates
        $newCron = new self($language);
        $newCron->_cronMinutes = $newCron->cronInterpret($elements[0], 0, 59, [], 'minutes');
        $newCron->_cronHours = $newCron->cronInterpret($elements[1], 0, 23, [], 'hours');
        $newCron->_cronDaysOfMonth = $newCron->cronInterpret($elements[2], 1, 31, [], 'daysOfMonth');
        $newCron->_cronMonths = $newCron->cronInterpret($elements[3], 1, 12, $arrMonths, 'months');
        $newCron->_cronDaysOfWeek = $newCron->cronInterpret($elements[4], 0, 6, $arrDaysOfWeek, 'daysOfWeek');


        $newCron->_minutes = $newCron->cronCreateItems($newCron->_cronMinutes);
        $newCron->_hours = $newCron->cronCreateItems($newCron->_cronHours);
        $newCron->_daysOfMonth = $newCron->cronCreateItems($newCron->_cronDaysOfMonth);
        $newCron->_months = $newCron->cronCreateItems($newCron->_cronMonths);
        $newCron->_daysOfWeek = $newCron->cronCreateItems($newCron->_cronDaysOfWeek);

        if (isset($elements[5])) {
            $newCron->_cronYears = $newCron->cronInterpret($elements[5], $newCron->RANGE_YEARS_MIN, $newCron->RANGE_YEARS_MAX, array(), 'years');
            $newCron->_years = $newCron->cronCreateItems($newCron->_cronYears);
        } else {
            $newCron->_cronYears = $newCron->cronInterpret('*', $newCron->RANGE_YEARS_MIN, $newCron->RANGE_YEARS_MAX, array(), 'years');
            $newCron->_years = $newCron->cronCreateItems($newCron->_cronYears);
        }

        return $newCron;
    }

    /**
     * @param $specification
     * @param $rangeMin
     * @param $rangeMax
     * @param $namedItems
     * @param $errorName
     * @return array
     * @throws \Exception
     */
    final private function cronInterpret($specification, $rangeMin, $rangeMax, $namedItems, $errorName)
    {

        if ((!is_string($specification)) && (!(is_int($specification)))) {
            throw new Exception('Invalid specification.');
        }

        $specs = [];
        $specs['rangeMin'] = $rangeMin;
        $specs['rangeMax'] = $rangeMax;
        $specs['elements'] = [];
        $arrSegments = explode(',', $specification);
        foreach ($arrSegments as $segment) {
            $hasRange = (($posRange = strpos($segment, '-')) !== FALSE);
            $hasInterval = (($posIncrement = strpos($segment, '/')) !== FALSE);

            if ($hasRange && $hasInterval) {
                if ($posIncrement < $posRange) {
                    throw new \Exception("Invalid order ($errorName).");
                }
            }

            $segmentNumber1 = $segment;
            $segmentNumber2 = '';
            $segmentIncrement = '';
            $intIncrement = 1;
            if ($hasInterval) {
                $segmentNumber1 = substr($segment, 0, $posIncrement);
                $segmentIncrement = substr($segment, $posIncrement + 1);
            }

            if ($hasRange) {
                $segmentNumber2 = substr($segmentNumber1, $posRange + 1);
                $segmentNumber1 = substr($segmentNumber1, 0, $posRange);
            }


            // Get and validate first value in range
            if ($segmentNumber1 == '*') {
                $intNumber1 = $rangeMin;
                $intNumber2 = $rangeMax;
                $hasRange = TRUE;
            } else {
                if (array_key_exists(strtoupper($segmentNumber1), $namedItems)) {
                    $segmentNumber1 = $namedItems[strtoupper($segmentNumber1)];
                }
                if (((string)($intNumber1 = (int)$segmentNumber1)) != $segmentNumber1) {
                    throw new \Exception("Invalid symbol ($errorName).");
                }
                if (($intNumber1 < $rangeMin) || ($intNumber1 > $rangeMax)) {
                    throw new \Exception("Out of bounds ($errorName).");
                }


                // Get and validate second value in range
                if ($hasRange) {
                    if (array_key_exists(strtoupper($segmentNumber2), $namedItems)) {
                        $segmentNumber2 = $namedItems[strtoupper($segmentNumber2)];
                    }
                    if (((string)($intNumber2 = (int)$segmentNumber2)) != $segmentNumber2) {
                        throw new \Exception("Invalid symbol ($errorName).");
                    }
                    if (($intNumber2 < $rangeMin) || ($intNumber2 > $rangeMax)) {
                        throw new \Exception("Out of bounds ($errorName).");
                    }
                    if ($intNumber1 > $intNumber2) {
                        throw new \Exception("Invalid range ($errorName).");
                    }
                }
            }


            // Get and validate increment
            if ($hasInterval) {
                if (($intIncrement = (int)$segmentIncrement) != $segmentIncrement) {
                    throw new \Exception("Invalid symbol ($errorName).");
                }
                if (($intIncrement < 1) || ($intIncrement > $rangeMax)) {
                    throw new \Exception("Out of bounds ($errorName).");
                }
            }


            // Apply range and increment

            $elem = [];
            $elem['number1'] = $intNumber1;
            $elem['hasInterval'] = $hasRange;
            if ($hasRange) {
                $elem['number2'] = $intNumber2;
                $elem['interval'] = $intIncrement;
            }
            $specs['elements'][] = $elem;
        }

        return $specs;
    }

    /**
     * @param $cronInterpreted
     * @return array
     */
    final private function cronCreateItems($cronInterpreted)
    {
        $items = [];

        foreach ($cronInterpreted['elements'] as $elem) {
            if (!$elem['hasInterval']) {
                // Nothing to do!
            } else {
                for ($number = $elem['number1']; $number <= $elem['number2']; $number += $elem['interval']) {
                    $items[$number] = TRUE;
                }
            }
        }
        ksort($items);
        return $items;
    }

    /**
     * @param bool $time
     * @return array|bool
     */
    final private function dtFromParameters($time = false)
    {
        if ($time === false) {
            $arrTime = getDate();
            return [$arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']];
        } else if (is_array($time)) {
            return $time;
        } else if (is_string($time)) {
            $arrTime = getDate(strtotime($time));
            return [$arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']];
        } else if (is_int($time)) {
            $arrTime = getDate($time);
            return [$arrTime['minutes'], $arrTime['hours'], $arrTime['mday'], $arrTime['mon'], $arrTime['year']];
        }
    }

    /**
     * @param $arrDt
     * @return bool|string
     */
    final private function dtAsString($arrDt)
    {
        if ($arrDt === false) {
            return false;
        }
        return $arrDt[4] . '-' . (strlen($arrDt[3]) == 1 ? '0' : '') . $arrDt[3] . '-' .
            (strlen($arrDt[2]) == 1 ? '0' : '') . $arrDt[2] . ' ' . (strlen($arrDt[1]) == 1 ? '0' : '') .
            $arrDt[1] . ':' . (strlen($arrDt[0]) == 1 ? '0' : '') . $arrDt[0] . ':00';
    }

    /**
     * @param bool $time
     * @return bool
     */
    final public function match($time = false)
    {

        // Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);

        // Verify match

        // Years
        if (!array_key_exists($arrDT[4], $this->_years)) {
            return false;
        }
        // Day of week
        if (!array_key_exists(date('w', strtotime($arrDT[4] . '-' . $arrDT[3] . '-' . $arrDT[2])), $this->_daysOfWeek)) {
            return false;
        }
        // Month
        if (!array_key_exists($arrDT[3], $this->_months)) {
            return false;
        }
        // Day of month
        if (!array_key_exists($arrDT[2], $this->_daysOfMonth)) {
            return false;
        }
        // Hours
        if (!array_key_exists($arrDT[1], $this->_hours)) {
            return false;
        }
        // Minutes
        if (!array_key_exists($arrDT[0], $this->_minutes)) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $time
     * @return array|bool
     */
    final public function next($time = false)
    {
        // Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);

        while (1) {
            // Verify the current date is in range. If not, move into range and consider this the next position
            if (!array_key_exists($arrDT[4], $this->_years)) {
                if (($arrDT[4] = $this->getEarliestItem($this->_years, $arrDT[4], false)) === false) {
                    return FALSE;
                }
                $arrDT[3] = $this->getEarliestItem($this->_months);
                $arrDT[2] = $this->getEarliestItem($this->_daysOfMonth);
                $arrDT[1] = $this->getEarliestItem($this->_hours);
                $arrDT[0] = $this->getEarliestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[3], $this->_months)) {
                $arrDT[3] = $this->getEarliestItem($this->_months, $arrDT[3]);
                $arrDT[2] = $this->getEarliestItem($this->_daysOfMonth);
                $arrDT[1] = $this->getEarliestItem($this->_hours);
                $arrDT[0] = $this->getEarliestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[2], $this->_daysOfMonth)) {
                $arrDT[2] = $this->getEarliestItem($this->_daysOfMonth, $arrDT[2]);
                $arrDT[1] = $this->getEarliestItem($this->_hours);
                $arrDT[0] = $this->getEarliestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[1], $this->_hours)) {
                $arrDT[1] = $this->getEarliestItem($this->_hours, $arrDT[1]);
                $arrDT[0] = $this->getEarliestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[1], $this->_hours)) {
                $arrDT[0] = $this->getEarliestItem($this->_minutes, $arrDT[0]);
                break;
            }

            // Advance minute, hour, date, month and year while overflowing.
            $daysInThisMonth = date('t', strtotime($arrDT[4] . '-' . $arrDT[3]));
            if ($this->advanceItem($this->_minutes, 0, 59, $arrDT[0])) {
                if ($this->advanceItem($this->_hours, 0, 23, $arrDT[1])) {
                    if ($this->advanceItem($this->_daysOfMonth, 0, $daysInThisMonth, $arrDT[2])) {
                        if ($this->advanceItem($this->_months, 1, 12, $arrDT[3])) {
                            if ($this->advanceItem($this->_years, $this->RANGE_YEARS_MIN, $this->RANGE_YEARS_MAX, $arrDT[4])) {
                                return false;
                            }
                        }
                    }
                }
            }
            break;
        }

        // If Datetime now points to a day that is schedule then return.
        $dayOfWeek = date('w', strtotime($this->dtAsString($arrDT)));
        if (array_key_exists($dayOfWeek, $this->_daysOfWeek)) {
            return $arrDT;
        }

        // Otherwise move to next scheduled date
        return $this->next($arrDT);
    }

    /**
     * @param bool $time
     * @return bool|string
     */
    final public function nextAsString($time = false)
    {
        return $this->dtAsString($this->next($time));
    }

    /**
     * @param bool $time
     * @return false|int
     */
    final public function nextAsTime($time = false)
    {
        return strtotime($this->dtAsString($this->next($time)));
    }

    /**
     * @param $arrItems
     * @param $rangeMin
     * @param $rangeMax
     * @param $current
     * @return bool
     */
    final private function advanceItem($arrItems, $rangeMin, $rangeMax, & $current)
    {
        // Advance pointer
        $current++;

        // If still before start, move to earliest
        if ($current < $rangeMin) {
            $current = $this->getEarliestItem($arrItems);
        }

        // Parse items until found or overflow
        for (; $current <= $rangeMax; $current++) {
            if (array_key_exists($current, $arrItems)) {
                return false; // We did not overflow
            }
        }

        // Or overflow
        $current = $this->getEarliestItem($arrItems);

        return true;
    }

    /**
     * @param $arrItems
     * @param bool $afterItem
     * @param bool $allowOverflow
     * @return bool|int|null|string
     */
    final private function getEarliestItem($arrItems, $afterItem = false, $allowOverflow = true)
    {
        // If no filter is specified, return the earliest listed item.
        if ($afterItem === false) {
            reset($arrItems);
            return key($arrItems);
        }

        // Or parse until we passed $afterItem
        foreach ($arrItems as $key => $value) {
            if ($key > $afterItem) {
                return $key;
            }
        }

        // If still nothing found, we may have exhausted our options.
        if (!$allowOverflow) {
            return false;
        }

        reset($arrItems);

        return key($arrItems);
    }

    /**
     * @param bool $time
     * @return array|bool
     */
    final public function previous($time = false)
    {
        // Convert parameters to array datetime
        $arrDT = $this->dtFromParameters($time);

        while (1) {
            // Verify the current date is in range. If not, move into range and consider this the previous position
            if (!array_key_exists($arrDT[4], $this->_years)) {
                if (($arrDT[4] = $this->getLatestItem($this->_years, $arrDT[4], false)) === false) {
                    return false;
                }
                $arrDT[3] = $this->getLatestItem($this->_months);
                $arrDT[2] = $this->getLatestItem($this->_daysOfMonth);
                $arrDT[1] = $this->getLatestItem($this->_hours);
                $arrDT[0] = $this->getLatestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[3], $this->_months)) {
                $arrDT[3] = $this->getLatestItem($this->_months, $arrDT[3]);
                $arrDT[2] = $this->getLatestItem($this->_daysOfMonth);
                $arrDT[1] = $this->getLatestItem($this->_hours);
                $arrDT[0] = $this->getLatestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[2], $this->_daysOfMonth)) {
                $arrDT[2] = $this->getLatestItem($this->_daysOfMonth, $arrDT[2]);
                $arrDT[1] = $this->getLatestItem($this->_hours);
                $arrDT[0] = $this->getLatestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[1], $this->_hours)) {
                $arrDT[1] = $this->getLatestItem($this->_hours, $arrDT[1]);
                $arrDT[0] = $this->getLatestItem($this->_minutes);
                break;
            } else if (!array_key_exists($arrDT[1], $this->_hours)) {
                $arrDT[0] = $this->getLatestItem($this->_minutes, $arrDT[0]);
                break;
            }

            // Recede minute, hour, date, month and year while overflowing.
            $daysInPreviousMonth = date('t', strtotime('-1 month', strtotime($arrDT[4] . '-' . $arrDT[3])));
            if ($this->recedeItem($this->_minutes, 0, 59, $arrDT[0])) {
                if ($this->recedeItem($this->_hours, 0, 23, $arrDT[1])) {
                    if ($this->recedeItem($this->_daysOfMonth, 0, $daysInPreviousMonth, $arrDT[2])) {
                        if ($this->recedeItem($this->_months, 1, 12, $arrDT[3])) {
                            if ($this->recedeItem($this->_years, $this->RANGE_YEARS_MIN, $this->RANGE_YEARS_MAX, $arrDT[4])) {
                                return false;
                            }
                        }
                    }
                }
            }
            break;
        }

        // If Datetime now points to a day that is schedule then return.
        $dayOfWeek = date('w', strtotime($this->dtAsString($arrDT)));
        if (array_key_exists($dayOfWeek, $this->_daysOfWeek)) {
            return $arrDT;
        }

        // Otherwise move to next scheduled date
        return $this->previous($arrDT);
    }

    /**
     * @param bool $time
     * @return bool|string
     */
    final public function previousAsString($time = false)
    {
        return $this->dtAsString($this->previous($time));
    }

    /**
     * @param bool $time
     * @return false|int
     */
    final public function previousAsTime($time = false)
    {
        return strtotime($this->dtAsString($this->previous($time)));
    }

    /**
     * @param $arrItems
     * @param $rangeMin
     * @param $rangeMax
     * @param $current
     * @return bool
     */
    final private function recedeItem($arrItems, $rangeMin, $rangeMax, & $current)
    {
        // Recede pointer
        $current--;

        // If still above highest, move to highest
        if ($current > $rangeMax) {
            $current = $this->getLatestItem($arrItems, $rangeMax + 1);
        }

        // Parse items until found or overflow
        for (; $current >= $rangeMin; $current--) {
            if (array_key_exists($current, $arrItems)) {
                return false; // We did not overflow
            }
        }

        // Or overflow
        $current = $this->getLatestItem($arrItems, $rangeMax + 1);
        return true;
    }

    /**
     * @param $arrItems
     * @param bool $beforeItem
     * @param bool $allowOverflow
     * @return bool|int|null|string
     */
    final private function getLatestItem($arrItems, $beforeItem = false, $allowOverflow = true)
    {
        // If no filter is specified, return the latestlisted item.
        if ($beforeItem === false) {
            end($arrItems);
            return key($arrItems);
        }

        // Or parse until we passed $beforeItem
        end($arrItems);
        do {
            if (($key = key($arrItems)) < $beforeItem) {
                return $key;
            }
        } while (prev($arrItems));

        // If still nothing found, we may have exhausted our options.
        if (!$allowOverflow) {
            return false;
        }

        end($arrItems);
        return key($arrItems);
    }

    /**
     * @param $spec
     * @return string
     */
    final private function getClass($spec)
    {
        if (!$this->classIsSpecified($spec)) {
            return '0';
        }
        if ($this->classIsSingleFixed($spec)) {
            return '1';
        }
        return '2';
    }

    /**
     * @param $spec
     * @return bool
     */
    final private function classIsSpecified($spec)
    {
        if ($spec['elements'][0]['hasInterval'] == false) {
            return true;
        }
        if ($spec['elements'][0]['number1'] != $spec['rangeMin']) {
            return true;
        }
        if ($spec['elements'][0]['number2'] != $spec['rangeMax']) {
            return true;
        }
        if ($spec['elements'][0]['interval'] != 1) {
            return true;
        }
        return false;
    }

    /**
     * @param $spec
     * @return bool
     */
    final private function classIsSingleFixed($spec)
    {
        return (count($spec['elements']) == 1) && (!$spec['elements'][0]['hasInterval']);
    }

    /**
     * @param string $language
     */
    final private function initLang($language = 'en')
    {
        switch ($language) {
            case 'en':
                $this->_lang['elemMin: at_the_hour'] = 'at the hour';
                $this->_lang['elemMin: after_the_hour_every_X_minute'] = 'every minute';
                $this->_lang['elemMin: after_the_hour_every_X_minute_plural'] = 'every @1 minutes';
                $this->_lang['elemMin: every_consecutive_minute'] = 'every consecutive minute';
                $this->_lang['elemMin: every_consecutive_minute_plural'] = 'every consecutive @1 minutes';
                $this->_lang['elemMin: every_minute'] = 'every minute';
                $this->_lang['elemMin: between_X_and_Y'] = 'from the @1 to the @2';
                $this->_lang['elemMin: at_X:Y'] = 'At @1:@2';
                $this->_lang['elemHour: past_X:00'] = 'past @1:00';
                $this->_lang['elemHour: between_X:00_and_Y:59'] = 'between @1:00 and @2:59';
                $this->_lang['elemHour: in_the_60_minutes_past_'] = 'in the 60 minutes past every consecutive hour';
                $this->_lang['elemHour: in_the_60_minutes_past__plural'] = 'in the 60 minutes past every consecutive @1 hours';
                $this->_lang['elemHour: past_every_consecutive_'] = 'past every consecutive hour';
                $this->_lang['elemHour: past_every_consecutive__plural'] = 'past every consecutive @1 hours';
                $this->_lang['elemHour: past_every_hour'] = 'past every hour';
                $this->_lang['elemDOM: the_X'] = 'the @1';
                $this->_lang['elemDOM: every_consecutive_day'] = 'every consecutive day';
                $this->_lang['elemDOM: every_consecutive_day_plural'] = 'every consecutive @1 days';
                $this->_lang['elemDOM: on_every_day'] = 'on every day';
                $this->_lang['elemDOM: between_the_Xth_and_Yth'] = 'between the @1 and the @2';
                $this->_lang['elemDOM: on_the_X'] = 'on the @1';
                $this->_lang['elemDOM: on_X'] = 'on @1';
                $this->_lang['elemMonth: every_X'] = 'every @1';
                $this->_lang['elemMonth: every_consecutive_month'] = 'every consecutive month';
                $this->_lang['elemMonth: every_consecutive_month_plural'] = 'every consecutive @1 months';
                $this->_lang['elemMonth: between_X_and_Y'] = 'from @1 to @2';
                $this->_lang['elemMonth: of_every_month'] = 'of every month';
                $this->_lang['elemMonth: during_every_X'] = 'during every @1';
                $this->_lang['elemMonth: during_X'] = 'during @1';
                $this->_lang['elemYear: in_X'] = 'in @1';
                $this->_lang['elemYear: every_consecutive_year'] = 'every consecutive year';
                $this->_lang['elemYear: every_consecutive_year_plural'] = 'every consecutive @1 years';
                $this->_lang['elemYear: from_X_through_Y'] = 'from @1 through @2';
                $this->_lang['elemDOW: on_every_day'] = 'on every day';
                $this->_lang['elemDOW: on_X'] = 'on @1';
                $this->_lang['elemDOW: but_only_on_X'] = 'but only if the event takes place on @1';
                $this->_lang['separator_and'] = 'and';
                $this->_lang['separator_or'] = 'or';
                $this->_lang['day: 0_plural'] = 'Sundays';
                $this->_lang['day: 1_plural'] = 'Mondays';
                $this->_lang['day: 2_plural'] = 'Tuesdays';
                $this->_lang['day: 3_plural'] = 'Wednesdays';
                $this->_lang['day: 4_plural'] = 'Thursdays';
                $this->_lang['day: 5_plural'] = 'Fridays';
                $this->_lang['day: 6_plural'] = 'Saturdays';
                $this->_lang['month: 1'] = 'January';
                $this->_lang['month: 2'] = 'February';
                $this->_lang['month: 3'] = 'March';
                $this->_lang['month: 4'] = 'April';
                $this->_lang['month: 5'] = 'May';
                $this->_lang['month: 6'] = 'June';
                $this->_lang['month: 7'] = 'July';
                $this->_lang['month: 8'] = 'Augustus';
                $this->_lang['month: 9'] = 'September';
                $this->_lang['month: 10'] = 'October';
                $this->_lang['month: 11'] = 'November';
                $this->_lang['month: 12'] = 'December';
                $this->_lang['ordinal: 1'] = '1st';
                $this->_lang['ordinal: 2'] = '2nd';
                $this->_lang['ordinal: 3'] = '3rd';
                $this->_lang['ordinal: 4'] = '4th';
                $this->_lang['ordinal: 5'] = '5th';
                $this->_lang['ordinal: 6'] = '6th';
                $this->_lang['ordinal: 7'] = '7th';
                $this->_lang['ordinal: 8'] = '8th';
                $this->_lang['ordinal: 9'] = '9th';
                $this->_lang['ordinal: 10'] = '10th';
                $this->_lang['ordinal: 11'] = '11th';
                $this->_lang['ordinal: 12'] = '12th';
                $this->_lang['ordinal: 13'] = '13th';
                $this->_lang['ordinal: 14'] = '14th';
                $this->_lang['ordinal: 15'] = '15th';
                $this->_lang['ordinal: 16'] = '16th';
                $this->_lang['ordinal: 17'] = '17th';
                $this->_lang['ordinal: 18'] = '18th';
                $this->_lang['ordinal: 19'] = '19th';
                $this->_lang['ordinal: 20'] = '20th';
                $this->_lang['ordinal: 21'] = '21st';
                $this->_lang['ordinal: 22'] = '22nd';
                $this->_lang['ordinal: 23'] = '23rd';
                $this->_lang['ordinal: 24'] = '24th';
                $this->_lang['ordinal: 25'] = '25th';
                $this->_lang['ordinal: 26'] = '26th';
                $this->_lang['ordinal: 27'] = '27th';
                $this->_lang['ordinal: 28'] = '28th';
                $this->_lang['ordinal: 29'] = '29th';
                $this->_lang['ordinal: 30'] = '30th';
                $this->_lang['ordinal: 31'] = '31st';
                $this->_lang['ordinal: 32'] = '32nd';
                $this->_lang['ordinal: 33'] = '33rd';
                $this->_lang['ordinal: 34'] = '34th';
                $this->_lang['ordinal: 35'] = '35th';
                $this->_lang['ordinal: 36'] = '36th';
                $this->_lang['ordinal: 37'] = '37th';
                $this->_lang['ordinal: 38'] = '38th';
                $this->_lang['ordinal: 39'] = '39th';
                $this->_lang['ordinal: 40'] = '40th';
                $this->_lang['ordinal: 41'] = '41st';
                $this->_lang['ordinal: 42'] = '42nd';
                $this->_lang['ordinal: 43'] = '43rd';
                $this->_lang['ordinal: 44'] = '44th';
                $this->_lang['ordinal: 45'] = '45th';
                $this->_lang['ordinal: 46'] = '46th';
                $this->_lang['ordinal: 47'] = '47th';
                $this->_lang['ordinal: 48'] = '48th';
                $this->_lang['ordinal: 49'] = '49th';
                $this->_lang['ordinal: 50'] = '50th';
                $this->_lang['ordinal: 51'] = '51st';
                $this->_lang['ordinal: 52'] = '52nd';
                $this->_lang['ordinal: 53'] = '53rd';
                $this->_lang['ordinal: 54'] = '54th';
                $this->_lang['ordinal: 55'] = '55th';
                $this->_lang['ordinal: 56'] = '56th';
                $this->_lang['ordinal: 57'] = '57th';
                $this->_lang['ordinal: 58'] = '58th';
                $this->_lang['ordinal: 59'] = '59th';
                break;

            case 'nl':
                $this->_lang['elemMin: at_the_hour'] = 'op het hele uur';
                $this->_lang['elemMin: after_the_hour_every_X_minute'] = 'elke minuut';
                $this->_lang['elemMin: after_the_hour_every_X_minute_plural'] = 'elke @1 minuten';
                $this->_lang['elemMin: every_consecutive_minute'] = 'elke opeenvolgende minuut';
                $this->_lang['elemMin: every_consecutive_minute_plural'] = 'elke opeenvolgende @1 minuten';
                $this->_lang['elemMin: every_minute'] = 'elke minuut';
                $this->_lang['elemMin: between_X_and_Y'] = 'van de @1 tot en met de @2';
                $this->_lang['elemMin: at_X:Y'] = 'Om @1:@2';
                $this->_lang['elemHour: past_X:00'] = 'na @1:00';
                $this->_lang['elemHour: between_X:00_and_Y:59'] = 'tussen @1:00 en @2:59';
                $this->_lang['elemHour: in_the_60_minutes_past_'] = 'in de 60 minuten na elk opeenvolgend uur';
                $this->_lang['elemHour: in_the_60_minutes_past__plural'] = 'in de 60 minuten na elke opeenvolgende @1 uren';
                $this->_lang['elemHour: past_every_consecutive_'] = 'na elk opeenvolgend uur';
                $this->_lang['elemHour: past_every_consecutive__plural'] = 'na elke opeenvolgende @1 uren';
                $this->_lang['elemHour: past_every_hour'] = 'na elk uur';
                $this->_lang['elemDOM: the_X'] = 'de @1';
                $this->_lang['elemDOM: every_consecutive_day'] = 'elke opeenvolgende dag';
                $this->_lang['elemDOM: every_consecutive_day_plural'] = 'elke opeenvolgende @1 dagen';
                $this->_lang['elemDOM: on_every_day'] = 'op elke dag';
                $this->_lang['elemDOM: between_the_Xth_and_Yth'] = 'tussen de @1 en de @2';
                $this->_lang['elemDOM: on_the_X'] = 'op de @1';
                $this->_lang['elemDOM: on_X'] = 'op @1';
                $this->_lang['elemMonth: every_X'] = 'elke @1';
                $this->_lang['elemMonth: every_consecutive_month'] = 'elke opeenvolgende maand';
                $this->_lang['elemMonth: every_consecutive_month_plural'] = 'elke opeenvolgende @1 maanden';
                $this->_lang['elemMonth: between_X_and_Y'] = 'van @1 tot @2';
                $this->_lang['elemMonth: of_every_month'] = 'van elke maand';
                $this->_lang['elemMonth: during_every_X'] = 'tijdens elke @1';
                $this->_lang['elemMonth: during_X'] = 'tijdens @1';
                $this->_lang['elemYear: in_X'] = 'in @1';
                $this->_lang['elemYear: every_consecutive_year'] = 'elk opeenvolgend jaar';
                $this->_lang['elemYear: every_consecutive_year_plural'] = 'elke opeenvolgende @1 jaren';
                $this->_lang['elemYear: from_X_through_Y'] = 'van @1 tot en met @2';
                $this->_lang['elemDOW: on_every_day'] = 'op elke dag';
                $this->_lang['elemDOW: on_X'] = 'op @1';
                $this->_lang['elemDOW: but_only_on_X'] = 'maar alleen als het plaatsvindt op @1';
                $this->_lang['separator_and'] = 'en';
                $this->_lang['separator_of'] = 'of';
                $this->_lang['day: 0_plural'] = 'zondagen';
                $this->_lang['day: 1_plural'] = 'maandagen';
                $this->_lang['day: 2_plural'] = 'dinsdagen';
                $this->_lang['day: 3_plural'] = 'woensdagen';
                $this->_lang['day: 4_plural'] = 'donderdagen';
                $this->_lang['day: 5_plural'] = 'vrijdagen';
                $this->_lang['day: 6_plural'] = 'zaterdagen';
                $this->_lang['month: 1'] = 'januari';
                $this->_lang['month: 2'] = 'februari';
                $this->_lang['month: 3'] = 'maart';
                $this->_lang['month: 4'] = 'april';
                $this->_lang['month: 5'] = 'mei';
                $this->_lang['month: 6'] = 'juni';
                $this->_lang['month: 7'] = 'juli';
                $this->_lang['month: 8'] = 'augustus';
                $this->_lang['month: 9'] = 'september';
                $this->_lang['month: 10'] = 'october';
                $this->_lang['month: 11'] = 'november';
                $this->_lang['month: 12'] = 'december';
                $this->_lang['ordinal: 1'] = '1e';
                $this->_lang['ordinal: 2'] = '2e';
                $this->_lang['ordinal: 3'] = '3e';
                $this->_lang['ordinal: 4'] = '4e';
                $this->_lang['ordinal: 5'] = '5e';
                $this->_lang['ordinal: 6'] = '6e';
                $this->_lang['ordinal: 7'] = '7e';
                $this->_lang['ordinal: 8'] = '8e';
                $this->_lang['ordinal: 9'] = '9e';
                $this->_lang['ordinal: 10'] = '10e';
                $this->_lang['ordinal: 11'] = '11e';
                $this->_lang['ordinal: 12'] = '12e';
                $this->_lang['ordinal: 13'] = '13e';
                $this->_lang['ordinal: 14'] = '14e';
                $this->_lang['ordinal: 15'] = '15e';
                $this->_lang['ordinal: 16'] = '16e';
                $this->_lang['ordinal: 17'] = '17e';
                $this->_lang['ordinal: 18'] = '18e';
                $this->_lang['ordinal: 19'] = '19e';
                $this->_lang['ordinal: 20'] = '20e';
                $this->_lang['ordinal: 21'] = '21e';
                $this->_lang['ordinal: 22'] = '22e';
                $this->_lang['ordinal: 23'] = '23e';
                $this->_lang['ordinal: 24'] = '24e';
                $this->_lang['ordinal: 25'] = '25e';
                $this->_lang['ordinal: 26'] = '26e';
                $this->_lang['ordinal: 27'] = '27e';
                $this->_lang['ordinal: 28'] = '28e';
                $this->_lang['ordinal: 29'] = '29e';
                $this->_lang['ordinal: 30'] = '30e';
                $this->_lang['ordinal: 31'] = '31e';
                $this->_lang['ordinal: 32'] = '32e';
                $this->_lang['ordinal: 33'] = '33e';
                $this->_lang['ordinal: 34'] = '34e';
                $this->_lang['ordinal: 35'] = '35e';
                $this->_lang['ordinal: 36'] = '36e';
                $this->_lang['ordinal: 37'] = '37e';
                $this->_lang['ordinal: 38'] = '38e';
                $this->_lang['ordinal: 39'] = '39e';
                $this->_lang['ordinal: 40'] = '40e';
                $this->_lang['ordinal: 41'] = '41e';
                $this->_lang['ordinal: 42'] = '42e';
                $this->_lang['ordinal: 43'] = '43e';
                $this->_lang['ordinal: 44'] = '44e';
                $this->_lang['ordinal: 45'] = '45e';
                $this->_lang['ordinal: 46'] = '46e';
                $this->_lang['ordinal: 47'] = '47e';
                $this->_lang['ordinal: 48'] = '48e';
                $this->_lang['ordinal: 49'] = '49e';
                $this->_lang['ordinal: 50'] = '50e';
                $this->_lang['ordinal: 51'] = '51e';
                $this->_lang['ordinal: 52'] = '52e';
                $this->_lang['ordinal: 53'] = '53e';
                $this->_lang['ordinal: 54'] = '54e';
                $this->_lang['ordinal: 55'] = '55e';
                $this->_lang['ordinal: 56'] = '56e';
                $this->_lang['ordinal: 57'] = '57e';
                $this->_lang['ordinal: 58'] = '58e';
                $this->_lang['ordinal: 59'] = '59e';
                break;
        }
    }

    /**
     * @param $number
     * @return string
     */
    final private function natlangPad2($number)
    {
        return (strlen($number) == 1 ? '0' : '') . $number;
    }

    /**
     * @param $id
     * @param bool $p1
     * @param bool $p2
     * @param bool $p3
     * @param bool $p4
     * @param bool $p5
     * @param bool $p6
     * @return mixed
     */
    final private function natlangApply($id, $p1 = false, $p2 = false, $p3 = false, $p4 = false, $p5 = false, $p6 = false)
    {
        $txt = $this->_lang[$id];

        if ($p1 !== false) {
            $txt = str_replace('@1', $p1, $txt);
        }
        if ($p2 !== false) {
            $txt = str_replace('@2', $p2, $txt);
        }
        if ($p3 !== false) {
            $txt = str_replace('@3', $p3, $txt);
        }
        if ($p4 !== false) {
            $txt = str_replace('@4', $p4, $txt);
        }
        if ($p5 !== false) {
            $txt = str_replace('@5', $p5, $txt);
        }
        if ($p6 !== false) {
            $txt = str_replace('@6', $p6, $txt);
        }

        return $txt;
    }

    /**
     * @param $spec
     * @param $entryFunction
     * @param bool $p1
     * @return string
     */
    final private function natlangRange($spec, $entryFunction, $p1 = false)
    {
        $arrIntervals = [];
        foreach ($spec['elements'] as $elem) {
            $arrIntervals[] = call_user_func($entryFunction, $elem, $p1);
        }

        $txt = "";
        for ($index = 0; $index < count($arrIntervals); $index++) {
            $txt .= ($index == 0 ? '' : ($index == (count($arrIntervals) - 1) ? ' ' . $this->natlangApply('separator_and') . ' ' : ', ')) . $arrIntervals[$index];
        }
        return $txt;
    }

    /**
     * @param $elem
     * @return mixed|string
     */
    final private function natlangElementMinute($elem)
    {
        if (!$elem['hasInterval']) {
            if ($elem['number1'] == 0) {
                return $this->natlangApply('elemMin: at_the_hour');
            } else {
                return $this->natlangApply('elemMin: after_the_hour_every_X_minute' . ($elem['number1'] == 1 ? '' : '_plural'), $elem['number1']);
            }
        }

        $txt = $this->natlangApply('elemMin: every_consecutive_minute' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->_cronMinutes['rangeMin']) || ($elem['number2'] != $this->_cronMinutes['rangeMax'])) {
            $txt .= ' (' . $this->natlangApply('elemMin: between_X_and_Y', $this->natlangApply('ordinal: ' . $elem['number1']), $this->natlangApply('ordinal: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }

    /**
     * @param $elem
     * @param $asBetween
     * @return mixed|string
     */
    final private function natlangElementHour($elem, $asBetween)
    {
        if (!$elem['hasInterval']) {
            if ($asBetween) {
                return $this->natlangApply('elemHour: between_X:00_and_Y:59', $this->natlangPad2($elem['number1']), $this->natlangPad2($elem['number1']));
            } else {
                return $this->natlangApply('elemHour: past_X:00', $this->natlangPad2($elem['number1']));
            }
        }

        if ($asBetween) {
            $txt = $this->natlangApply('elemHour: in_the_60_minutes_past_' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        } else {
            $txt = $this->natlangApply('elemHour: past_every_consecutive_' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        }

        if (($elem['number1'] != $this->_cronHours['rangeMin']) || ($elem['number2'] != $this->_cronHours['rangeMax'])) {
            $txt .= ' (' . $this->natlangApply('elemHour: between_X:00_and_Y:59', $elem['number1'], $elem['number2']) . ')';
        }
        return $txt;
    }

    /**
     * @param $elem
     * @return mixed|string
     */
    final private function natlangElementDayOfMonth($elem)
    {
        if (!$elem['hasInterval']) {
            return $this->natlangApply('elemDOM: the_X', $this->natlangApply('ordinal: ' . $elem['number1']));
        }

        $txt = $this->natlangApply('elemDOM: every_consecutive_day' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->_cronHours['rangeMin']) || ($elem['number2'] != $this->_cronHours['rangeMax'])) {
            $txt .= ' (' . $this->natlangApply('elemDOM: between_the_Xth_and_Yth', $this->natlangApply('ordinal: ' . $elem['number1']), $this->natlangApply('ordinal: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }

    /**
     * @param $elem
     * @return mixed|string
     */
    final private function natlangElementMonth($elem)
    {
        if (!$elem['hasInterval']) {
            return $this->natlangApply('elemMonth: every_X', $this->natlangApply('month: ' . $elem['number1']));
        }

        $txt = $this->natlangApply('elemMonth: every_consecutive_month' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->_cronMonths['rangeMin']) || ($elem['number2'] != $this->_cronMonths['rangeMax'])) {
            $txt .= ' (' . $this->natlangApply('elemMonth: between_X_and_Y', $this->natlangApply('month: ' . $elem['number1']), $this->natlangApply('month: ' . $elem['number2'])) . ')';
        }
        return $txt;
    }


    /**
     * @param $elem
     * @return mixed|string
     */
    final private function natlangElementYear($elem)
    {
        if (!$elem['hasInterval']) {
            return $elem['number1'];
        }

        $txt = $this->natlangApply('elemYear: every_consecutive_year' . ($elem['interval'] == 1 ? '' : '_plural'), $elem['interval']);
        if (($elem['number1'] != $this->_cronMonths['rangeMin']) || ($elem['number2'] != $this->_cronMonths['rangeMax'])) {
            $txt .= ' (' . $this->natlangApply('elemYear: from_X_through_Y', $elem['number1'], $elem['number2']) . ')';
        }
        return $txt;
    }

    /**
     * @return string
     */
    final public function asNaturalLanguage()
    {
        $switchForceDateExplaination = false;
        $switchDaysOfWeekAreExcluding = true;

        // Generate Time String
        $txtMinutes = [];
        $txtMinutes[0] = $this->natlangApply('elemMin: every_minute');
        $txtMinutes[1] = $this->natlangElementMinute($this->_cronMinutes['elements'][0]);
        $txtMinutes[2] = $this->natlangRange($this->_cronMinutes, [$this, 'natlangElementMinute']);

        $txtHours = [];
        $txtHours[0] = $this->natlangApply('elemHour: past_every_hour');
        $txtHours[1] = [];
        $txtHours[1]['between'] = $this->natlangRange($this->_cronHours, [$this, 'natlangElementHour'], true);
        $txtHours[1]['past'] = $this->natlangRange($this->_cronHours, [$this, 'natlangElementHour'], false);
        $txtHours[2] = [];
        $txtHours[2]['between'] = $this->natlangRange($this->_cronHours, [$this, 'natlangElementHour'], true);
        $txtHours[2]['past'] = $this->natlangRange($this->_cronHours, [$this, 'natlangElementHour'], false);

        $classMinutes = $this->getClass($this->_cronMinutes);
        $classHours = $this->getClass($this->_cronHours);

        switch ($classMinutes . $classHours) {
            // Special case: Unspecified date + Unspecified month
            //
            // Rule: The language for unspecified fields is omitted if a more detailed field has already been explained.
            //
            // The minutes field always yields an explaination, at the very least in the form of 'every minute'. This rule states that if the
            // hour is not specified, it can be omitted because 'every minute' is already sufficiently clear.
            //
            case '00':
                $txtTime = $txtMinutes[0];
                break;

            // Special case: Fixed minutes and fixed hours
            //
            // The default writing would be something like 'every 20 minutes past 04:00', but the more common phrasing would be: At 04:20.
            //
            // We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
            //
            case '11':
                $txtTime = $this->natlangApply('elemMin: at_X:Y', $this->natlangPad2($this->_cronHours['elements'][0]['number1']), $this->natlangPad2($this->_cronMinutes['elements'][0]['number1']));
                $switchForceDateExplaination = TRUE;
                break;

            // Special case: Between :00 and :59
            //
            // If hours are specified, but minutes are not, then the minutes string will yield something like 'every minute'. We must the
            // differentiate the hour specification because the minutes specification does not relate to all minutes past the hour, but only to
            // those minutes between :00 and :59
            //
            // We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
            //
            case '01':
            case '02':
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours]['between'];
                $switchForceDateExplaination = TRUE;
                break;

            // Special case: Past the hour
            //
            // If minutes are specified and hours are specified, then the specification of minutes is always limited to a maximum of 60 minutes
            // and always applies to the minutes 'past the hour'.
            //
            // We will switch ForceDateExplaination on, so that even a non-specified date yields an explaination (e.g. 'every day')
            //
            case '12':
            case '22':
            case '21':
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours]['past'];
                $switchForceDateExplaination = TRUE;
                break;

            default:
                $txtTime = $txtMinutes[$classMinutes] . ' ' . $txtHours[$classHours];
                break;
        }


        // Generate Date String
        $txtDaysOfMonth = [];
        $txtDaysOfMonth[0] = '';
        $txtDaysOfMonth[1] = $this->natlangApply('elemDOM: on_the_X', $this->natlangApply('ordinal: ' . $this->_cronDaysOfMonth['elements'][0]['number1']));
        $txtDaysOfMonth[2] = $this->natlangApply('elemDOM: on_X', $this->natlangRange($this->_cronDaysOfMonth, [$this, 'natlangElementDayOfMonth']));

        $txtMonths = [];
        $txtMonths[0] = $this->natlangApply('elemMonth: of_every_month');
        $txtMonths[1] = $this->natlangApply('elemMonth: during_every_X', $this->natlangApply('month: ' . $this->_cronMonths['elements'][0]['number1']));
        $txtMonths[2] = $this->natlangApply('elemMonth: during_X', $this->natlangRange($this->_cronMonths, [$this, 'natlangElementMonth']));

        $classDaysOfMonth = $this->getClass($this->_cronDaysOfMonth);
        $classMonths = $this->getClass($this->_cronMonths);

        if ($classDaysOfMonth == '0') {
            $switchDaysOfWeekAreExcluding = FALSE;
        }

        switch ($classDaysOfMonth . $classMonths) {
            // Special case: Unspecified date + Unspecified month
            //
            // Rule: The language for unspecified fields is omitted if a more detailed field has already been explained.
            //
            // The time fields always yield an explaination, at the very least in the form of 'every minute'. This rule states that if the date
            // is not specified, it can be omitted because 'every minute' is already sufficiently clear.
            //
            // There are some time specifications that do not contain an 'every' reference, but reference a specific time of day. In those cases
            // the date explaination is enforced.
            //
            case '00':
                $txtDate = '';
                break;

            default:
                $txtDate = ' ' . $txtDaysOfMonth[$classDaysOfMonth] . ' ' . $txtMonths[$classMonths];
                break;
        }


        // Generate Year String

        if ($this->_cronYears) {
            $txtYears = [];
            $txtYears[0] = '';
            $txtYears[1] = ' ' . $this->natlangApply('elemYear: in_X', $this->_cronYears['elements'][0]['number1']);
            $txtYears[2] = ' ' . $this->natlangApply('elemYear: in_X', $this->natlangRange($this->_cronYears, [$this, 'natlangElementYear']));

            $classYears = $this->getClass($this->_cronYears);
            $txtYear = $txtYears[$classYears];
        }


        // Generate DaysOfWeek String

        $collectDays = 0;
        foreach ($this->_cronDaysOfWeek['elements'] as $elem) {
            if ($elem['hasInterval']) {
                for ($x = $elem['number1']; $x <= $elem['number2']; $x += $elem['interval']) {
                    $collectDays |= pow(2, $x);
                }
            } else {
                $collectDays |= pow(2, $elem['number1']);
            }
        }
        if ($collectDays == 127)    // * all days
        {
            if (!$switchDaysOfWeekAreExcluding) {
                $txtDays = ' ' . $this->natlangApply('elemDOM: on_every_day');
            } else {
                $txtDays = '';
            }
        } else {
            $arrDays = [];
            for ($x = 0; $x <= 6; $x++) {
                if ($collectDays & pow(2, $x)) {
                    $arrDays[] = $x;
                }
            }
            $txtDays = '';
            for ($index = 0; $index < count($arrDays); $index++) {
                $txtDays .= ($index == 0 ? '' : ($index == (count($arrDays) - 1) ? ' ' . $this->natlangApply($switchDaysOfWeekAreExcluding ? 'separator_or' : 'separator_and') . ' ' : ', ')) . $this->natlangApply('day: ' . $arrDays[$index] . '_plural');
            }
            if ($switchDaysOfWeekAreExcluding) {
                $txtDays = ' ' . $this->natlangApply('elemDOW: but_only_on_X', $txtDays);
            } else {
                $txtDays = ' ' . $this->natlangApply('elemDOW: on_X', $txtDays);
            }
        }

        $txtResult = ucfirst($txtTime) . $txtDate . $txtDays;

        if (isset($txtYear)) {
            if ($switchDaysOfWeekAreExcluding) {
                $txtResult = ucfirst($txtTime) . $txtDate . $txtYear . $txtDays;
            } else {
                $txtResult = ucfirst($txtTime) . $txtDate . $txtDays . $txtYear;
            }
        }

        return $txtResult . '.';
    }
}