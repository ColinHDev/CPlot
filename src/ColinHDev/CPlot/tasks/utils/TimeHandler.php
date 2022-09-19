<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use function floor;
use function is_int;
use function microtime;
use function round;

/**
 * Utility class to easily work with the execution time of asynchronous operations, e.g. a query or an AsyncTask.
 */
final class TimeHandler {

    private int $startTime;
    private ?int $stopTime = null;

    /**
     * Constructs a new {@see TimeHandler} object. This automatically starts the time for the object.
     */
    public function __construct() {
        $this->startTime = (int) (round(microtime(true) * 1000));
    }

    /**
     * Stops the time for the object so any future {@see getElapsedTime()} call returns the same number of milli seconds.
     */
    public function stopTime() : void {
        $this->stopTime = (int) (round(microtime(true) * 1000));
    }

    /**
     * Returns the elapsed time, either since the object was created or between its creation and the last call of {@see stopTime()}.
     */
    public function getElapsedTime() : int {
        if (is_int($this->stopTime)) {
            return $this->stopTime - $this->startTime;
        }
        return ((int) (round(microtime(true) * 1000))) - $this->startTime;
    }

    /**
     * Formats a number of milli seconds into a human-readable string.
     * @param int $ms the number of milli seconds, e.g. 64309
     * @return string the formatted time string, e.g. "1min, 4s, 309ms"
     */
    public static function formatMilliseconds(int $ms) : string {
        $min = floor($ms / 60000);
        $ms %= 60000;
        $s = floor($ms / 1000);
        $ms %= 1000;
        $formattedTime = "";
        if ($min > 0) {
            $formattedTime .= $min . "min";
        }
        if ($s > 0) {
            if ($formattedTime !== "") $formattedTime .= ", ";
            $formattedTime .= $s . "s";
        }
        if ($ms > 0) {
            if ($formattedTime !== "") $formattedTime .= ", ";
            $formattedTime .= $ms . "ms";
        }
        return $formattedTime;
    }
}