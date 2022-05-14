<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

    private int $startTime;

    public function __construct() {
        $this->startTime = (int) (round(microtime(true) * 1000));
    }

    public function getElapsedTime() : int {
        return ((int) (round(microtime(true) * 1000))) - $this->startTime;
    }

    public function getElapsedTimeString() : string {
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
     * @phpstan-param (callable(static): void)|null $onSuccess
     * @phpstan-param (callable(static): void)|null $onError
     */
    public function setCallback(?callable $onSuccess, ?callable $onError) : void {
        if ($onSuccess !== null) {
            $this->storeLocal("onSuccess", $onSuccess);
        }
        if ($onError !== null) {
            $this->storeLocal("onError", $onError);
        }
    }

    public function onCompletion() : void {
        try {
            /** @phpstan-var callable(static): void $callback */
            $callback = $this->fetchLocal("onSuccess");
            $callback($this);
        } catch (\InvalidArgumentException) {
        }
    }

    public function onError() : void {
        try {
            /** @phpstan-var callable(static): void $callback */
            $callback = $this->fetchLocal("onError");
            $callback($this);
        } catch (\InvalidArgumentException) {
        }
    }
}