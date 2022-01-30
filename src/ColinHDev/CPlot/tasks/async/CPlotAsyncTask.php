<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

    private int $startTime;

    public function __construct() {
        $this->startTime = (int) (round(microtime(true) * 1000));
    }

    protected function getElapsedTime() : int {
        return ((int) (round(microtime(true) * 1000))) - $this->startTime;
    }

    protected function getElapsedTimeString() : string {
        $ms = $this->getElapsedTime();
        $min = floor($ms / 60000);
        $ms -= $min * 60000;
        $s = floor($ms / 1000);
        $ms -= $s * 1000;
        $time = "";
        if ($min > 0) {
            $time .= $min . "min";
        }
        if ($s > 0) {
            if ($time !== "") $time .= ", ";
            $time .= $s . "s";
        }
        if ($ms > 0) {
            if ($time !== "") $time .= ", ";
            $time .= $ms . "ms";
        }
        return $time;
    }

    /**
     * @phpstan-param callable(int, string, mixed): void $onSuccess
     * @phpstan-param null|callable(): void $onError
     */
    public function setCallback(callable $onSuccess, ?callable $onError = null) : void {
        $this->storeLocal("onSuccess", $onSuccess);
        if ($onError !== null) {
            $this->storeLocal("onError", $onError);
        }
    }

    public function onCompletion() : void {
        try {
            /** @phpstan-var callable(int, string, mixed): void $callback */
            $callback = $this->fetchLocal("onSuccess");
            $callback($this->getElapsedTime(), $this->getElapsedTimeString(), $this->getResult());
        } catch (\InvalidArgumentException) {
        }
    }

    public function onError() : void {
        try {
            /** @phpstan-var callable(): void $callback */
            $callback = $this->fetchLocal("onError");
            $callback();
        } catch (\InvalidArgumentException) {
        }
    }
}