<?php

namespace Alpha\Model\Type;

use Alpha\Util\Helper\Validator;
use Alpha\Exception\IllegalArguementException;
use Alpha\Util\Config\ConfigProvider;

/**
 * The Timestamp complex data type.
 *
 * @since 1.0
 *
 * @author John Collins <dev@alphaframework.org>
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2017, John Collins (founder of Alpha Framework).
 * All rights reserved.
 *
 * <pre>
 * Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the
 * following conditions are met:
 *
 * * Redistributions of source code must retain the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above
 *   copyright notice, this list of conditions and the
 *   following disclaimer in the documentation and/or other
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names
 *   of its contributors may be used to endorse or promote
 *   products derived from this software without specific
 *   prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 */
class Timestamp extends Type implements TypeInterface
{
    /**
     * The year part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $year;

    /**
     * The month part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $month;

    /**
     * The day part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $day;

    /**
     * The hour part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $hour;

    /**
     * The minute part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $minute;

    /**
     * The second part.
     *
     * @var int
     *
     * @since 1.0
     */
    private $second;

    /**
     * The textual version of the day, e.g. Monday.
     *
     * @var string
     *
     * @since 1.0
     */
    private $weekday;

    /**
     * The validation rule (reg-ex) applied to Date values.
     *
     * @var string
     *
     * @since 1.0
     */
    private $validationRule;

    /**
     * The error message returned for invalid values.
     *
     * @var string
     *
     * @since 1.0
     */
    private $helper = 'Not a valid timestamp value!  A timestamp should be in the format YYYY-MM-DD HH:MM:SS.';

    /**
     * Constructor.
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function __construct($timestamp = '')
    {
        $config = ConfigProvider::getInstance();

        $this->validationRule = Validator::ALLOW_ALL;

        if (empty($timestamp)) {
            if ($config->get('app.default.datetime') == 'now') {
                $this->year = date('Y');
                $this->month = date('m');
                $this->day = date('d');
                $this->weekday = date('l');
                $this->hour = date('H');
                $this->minute = date('i');
                $this->second = date('s');
            } else {
                $this->year = '0000';
                $this->month = '00';
                $this->day = '00';
                $this->hour = '00';
                $this->minute = '00';
                $this->second = '00';
            }
        } else {
            if (preg_match($this->validationRule, $timestamp)) {
                $this->populateFromString($timestamp);
            } else {
                throw new IllegalArguementException($this->helper);
            }
        }
    }

    /**
     * Accepts a full date/time string in YYYY-mm-dd hh:ii:ss format.
     *
     * @param string $dateTime
     *
     * @since 1.0
     */
    public function setValue($dateTime)
    {
        $this->populateFromString($dateTime);
    }

    /**
     * Setter for the timestamp value.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function setTimestampValue($year, $month, $day, $hour, $minute, $second)
    {
        $valid = null;

        if (!preg_match('/^[0-9]{4}$/', $year)) {
            $valid = 'The year value '.$year.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month)) {
            $valid = 'The month value '.$month.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day)) {
            $valid = 'The day value '.$day.' provided is invalid!';
        }
        if (!isset($valid) && !checkdate($month, $day, $year)) {
            $valid = 'The day value '.$year.'-'.$month.'-'.$day.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24)) {
            $valid = 'The hour value '.$hour.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60)) {
            $valid = 'The minute value '.$minute.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60)) {
            $valid = 'The second value '.$second.' provided is invalid!';
        }

        if (isset($valid)) {
            throw new IllegalArguementException($valid);
        } else {
            $this->year = $year;
            $this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
            $this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
            $this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
            $unixTime = mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
            $this->weekday = date('l', $unixTime);
        }
    }

    /**
     * Getter for the Timestamp value.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getValue()
    {
        return $this->year.'-'.$this->month.'-'.$this->day.' '.$this->hour.':'.$this->minute.':'.$this->second;
    }

    /**
     * Return the value in UNIX timestamp format.
     *
     * @return int
     *
     * @since 1.0
     */
    public function getUnixValue()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }

    /**
     * Getter for the date part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getDate()
    {
        return $this->year.'-'.$this->month.'-'.$this->day;
    }

    /**
     * Get the date value as a string in the format "DD/MM/YYYY".
     *
     * @return string
     *
     * @since 1.0
     */
    public function getEuroValue()
    {
        return $this->day.'/'.$this->month.'/'.mb_substr($this->year, 2, 2);
    }

    /**
     * Setter for the date part.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function setDate($year, $month, $day)
    {
        $valid = null;

        if (!preg_match('/^[0-9]{4}$/', $year)) {
            $valid = 'The year value '.$year.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month)) {
            $valid = 'The month value '.$month.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day)) {
            $valid = 'The day value '.$day.' provided is invalid!';
        }
        if (!isset($valid) && !checkdate($month, $day, $year)) {
            $valid = 'The day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
        }

        if (isset($valid)) {
            throw new IllegalArguementException($valid);
        } else {
            $this->year = $year;
            $this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
            $this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
            $unixTime = mktime(0, 0, 0, $this->month, $this->day, $this->year);
            $this->weekday = date('l', $unixTime);
        }
    }

    /**
     * Getter for the time part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getTime()
    {
        return $this->hour.':'.$this->minute.':'.$this->second;
    }

    /**
     * Getter for the year part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Getter for the month part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Getter for the day part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Get the textual weekday part, e.g. Monday.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getWeekday()
    {
        return $this->weekday;
    }

    /**
     * Getter for the hour part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Getter for the minute part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * Getter for the second part.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getSecond()
    {
        return $this->second;
    }

    /**
     * Setter for the time part.
     *
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function setTime($hour, $minute, $second)
    {
        $valid = null;

        if (!isset($valid) && !preg_match('/^[0-9]{2}$/', $hour) || !($hour >= 0 && $hour < 24)) {
            $valid = 'The hour value '.$hour.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60)) {
            $valid = 'The minute value '.$minute.' provided is invalid!';
        }
        if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60)) {
            $valid = 'The second value '.$second.' provided is invalid!';
        }

        if (isset($valid)) {
            throw new IllegalArguementException($valid);
        } else {
            $this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
            $this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Accepts a full date/time string in YYYY-mm-dd hh:ii:ss format.
     *
     * @param string $dateTime
     *
     * @since 1.0
     *
     * @throws Alpha\Exception\IllegalArguementException
     */
    public function populateFromString($dateTime)
    {
        $valid = null;

        if ($dateTime == 'Please select' || $dateTime == '0000-00-00 00:00:00') {
            $this->year = '0000';
            $this->month = '00';
            $this->day = '00';
            $this->hour = '00';
            $this->minute = '00';
            $this->second = '00';
        } else {
            $spilt_by_space = explode(' ', $dateTime);

            if (isset($spilt_by_space[0])) {
                $date = $spilt_by_space[0];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (isset($spilt_by_space[1])) {
                $time = $spilt_by_space[1];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            $split_by_dash = explode('-', $date);

            if (isset($split_by_dash[0])) {
                $year = $split_by_dash[0];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (isset($split_by_dash[1])) {
                $month = $split_by_dash[1];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (isset($split_by_dash[2])) {
                $day = $split_by_dash[2];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            $split_by_colon = explode(':', $time);

            if (isset($split_by_colon[0])) {
                $hour = $split_by_colon[0];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (isset($split_by_colon[1])) {
                $minute = $split_by_colon[1];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (isset($split_by_colon[2])) {
                $second = $split_by_colon[2];
            } else {
                throw new IllegalArguementException($this->helper);
            }

            if (!preg_match('/^[0-9]{4}$/', $year)) {
                $valid = 'The year value '.$year.' provided is invalid!';
            }
            if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $month)) {
                $valid = 'The month value '.$month.' provided is invalid!';
            }
            if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $day)) {
                $valid = 'The day value '.$day.' provided is invalid!';
            }
            if (!isset($valid) && !checkdate($month, $day, $year)) {
                $valid = 'The day value '.$year.'/'.$month.'/'.$day.' provided is invalid!';
            }
            if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $hour) || !($hour >= 0 && $hour < 24)) {
                $valid = 'The hour value '.$hour.' provided is invalid!';
            }
            if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $minute) || !($minute >= 0 && $minute < 60)) {
                $valid = 'The minute value '.$minute.' provided is invalid!';
            }
            if (!isset($valid) && !preg_match('/^[0-9]{1,2}$/', $second) || !($second >= 0 && $second < 60)) {
                $valid = 'The second value '.$second.' provided is invalid!';
            }

            if (isset($valid)) {
                throw new IllegalArguementException($valid);
            } else {
                $this->year = $year;
                $this->month = str_pad($month, 2, '0', STR_PAD_LEFT);
                $this->day = str_pad($day, 2, '0', STR_PAD_LEFT);
                $this->hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
                $this->minute = str_pad($minute, 2, '0', STR_PAD_LEFT);
                $this->second = str_pad($second, 2, '0', STR_PAD_LEFT);
                $unixTime = mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
                $this->weekday = date('l', $unixTime);
            }
        }
    }

    /**
     * Get the validation rule.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getRule()
    {
        return $this->validationRule;
    }

    /**
     * Set the validation rule.
     *
     * @param string $rule
     *
     * @since 1.0
     */
    public function setRule($rule)
    {
        $this->validationRule = $rule;
    }

    /**
     * Get the validation helper text.
     *
     * @return string
     *
     * @since 1.0
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * Set the validation helper text.
     *
     * @param string $helper
     *
     * @since 1.0
     */
    public function setHelper($helper)
    {
        $this->helper = $helper;
    }

    /**
     * Returns the difference between now and this timestamp value, in a human-readable format, e.g: 3 days ago, 3 days from now.
     *
     * @return string
     *
     * @since 1.2.4
     */
    public function getTimeAway()
    {
        $periods = array('second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade');
        $lengths = array('60', '60', '24', '7', '4.35', '12', '10');

        $now = time();
        $unixTS = $this->getUnixValue();

        if ($now > $unixTS) {
            $difference = $now - $unixTS;
            $tense = 'ago';
        } else {
            $difference = $unixTS - $now;
            $tense = 'from now';
        }

        for ($i = 0; $difference >= $lengths[$i] && $i < count($lengths) - 1; ++$i) {

            $difference = round($difference / $lengths[$i]);
        }

        $difference = round($difference);

        if ($difference != 1) {
            $periods[$i] .= 's';
        }

        return $difference.' '.$periods[$i].' '.$tense;
    }
}
