<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\tasks\async\CPlotAsyncTask;
use function floor;

/**
 * Simple class to represent the result of an {@see CPlotAsyncTask}.
 * This class is only used if the task was successful, not if it failed!
 */
final class AsyncTaskResult {

    private int $executionTime;
    private mixed $result;

    public function __construct(int $executionTime, mixed $result) {
        $this->executionTime = $executionTime;
        $this->result = $result;
    }

    /**
     * Returns the time it took to execute the {@see CPlotAsyncTask} in milliseconds.
     */
    public function getExecutionTime() : int {
        return $this->executionTime;
    }

    /**
     * Returns the time it took to execute the {@see CPlotAsyncTask} as a readable string, e.g. "2min, 45s, 145ms".
     */
    public function getFormattedExecutionTime() : string {
        $ms = $this->executionTime;
        $min = floor($ms / 60000);
        $ms -= $min * 60000;
        $s = floor($ms / 1000);
        $ms -= $s * 1000;
        $time = "";
        if ($min > 0) {
            $time .= $min . "min";
        }
        if ($s > 0) {
            if ($time !== "") {
                $time .= ", ";
            }
            $time .= $s . "s";
        }
        if ($ms > 0) {
            if ($time !== "") {
                $time .= ", ";
            }
            $time .= $ms . "ms";
        }
        return $time;
    }

    /**
     * Returns true if the {@see CPlotAsyncTask} had returned a specific value as result.
     * This has nothing to do whether the task was successful or not.
     */
    public function hasResult() : bool {
        return $this->result !== null;
    }

    /**
     * Returns the result value of the {@see CPlotAsyncTask} or null, if none was set.
     */
    public function getResult() : mixed {
        return $this->result;
    }
}