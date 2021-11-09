<?php

namespace Zoomx\Support;


use modX;

class DateFormatter
{
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 3600 * 24;

    /** @var modX $modx */
    protected $modx;
    /** @var array $config */
    protected $config;
    /** @var array */
    protected $months = [];

    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->config = array_merge([
            'dateFormat' => 'd F Y, H:i',
            'NearDayFormat' => '[[+day]] H:i',
            'secondsLimit' => 20,
            'minutesLimit' => 60,
            'hoursLimit' => 24,
            'daysLimit' => 7,
        ],
        $config);

        $this->months = json_decode($modx->lexicon('zoomx_date_months'), true);
    }

    /**
     * Display fuzzy date (yesterday at 11:15, today at 06:00).
     * @param int|string $date
     * @param string $format
     * @return string
     */
    public function fuzzyDate($date, $format = '')
    {
        if (!empty($date)) {
            $date = !is_numeric($date) ? strtotime($date) : $date;

            $output = $this->checkNearDate($date);

            if ($output === null) {
                return date($this->getDateFormat($date, $format), $date);
            }
        }

        return $output;
    }
    
    public function dateAgo($date, $format = '')
    {
        if (empty(trim($date))) {
            return $date;
        }
        $date = is_numeric($date) ? (int)$date : strtotime($date);
        $delta = time() - $date;

        if ($delta >= 0 ) {
            if ($delta <= $this->config['secondsLimit']) {
                return $this->modx->lexicon('zoomx_just_now');
            }
            $minutes = floor($delta / self::MINUTE);
            if ($minutes < $this->config['minutesLimit']) {
                return $minutes > 0
                    ? $this->declension($minutes, json_decode($this->modx->lexicon('zoomx_date_minutes_back', ['minutes' => $minutes])), true)
                    : $this->modx->lexicon('zoomx_date_minutes_back_less');
            }
            $hours = floor($delta / self::HOUR);
            if ($hours < $this->config['hoursLimit']) {
                return $this->declension($hours, json_decode($this->modx->lexicon('zoomx_date_hours_back', ['hours' => $hours])), true);
            }
            $days = floor($delta / self::DAY);
            if ($days < $this->config['daysLimit']) {
                return $this->declension($days, json_decode($this->modx->lexicon('zoomx_date_days_back', ['days' => $days])), true);
            }
        }

        return date($this->getDateFormat($date, $format), $date);
    }

    /**
     * Declension of words.
     *
     * @param int $number
     * @param array $words
     * @param bool $include
     *
     * @return string
     */
    public function declension($number, array $words)
    {
        $number = (int)$number;
        if (zoomx()->config('cultureKey') === 'ru') {
            $keys = [2, 0, 1, 1, 1, 2];
            if (count($words) < 3) {
                $words[2] = $words[1];
            }
            $key = ($number % 100 > 4 && $number % 100 < 20) ? 2 : $keys[min($number % 10, 5)];
        } else {
            $key = $number === 1 ? 0 : 1;
        }

        return $words[$key];
    }

    /**
     * @param $date
     * @return false|string|null
     */
    protected function checkNearDate($date)
    {
        switch (date('Y-m-d', $date)) {
            case date('Y-m-d'):
                $day = $this->modx->lexicon('zoomx_date_today');
                break;
            case date('Y-m-d', strtotime('Yesterday')):
                $day = $this->modx->lexicon('zoomx_date_yesterday');
                break;
            case date('Y-m-d', strtotime('Tomorrow')):
                $day = $this->modx->lexicon('zoomx_date_tomorrow');
                break;
            default:
                $day = null;
        }
        if ($day) {
            $format = str_replace("[[+day]]", preg_replace("#(\w{1})#", '\\\${1}', $day), $this->config['NearDayFormat']);

            return date($format, $date);
        }

        return null;
    }

    private function getDateFormat($date, $format)
    {
        $monthNumber = date("n", $date) - 1;
        $format = !empty($format) ? $format : $this->config['dateFormat'];

        return preg_replace("~(?<!\\\\)F~U", preg_replace('~(\w{1})~u', '\\\${1}', $this->months[$monthNumber]), $format);
    }
}