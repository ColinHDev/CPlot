<?php

namespace ColinHDev\CPlot\tasks\async;

use Closure;
use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

    private int $startTime;

    private bool $hasCallback = false;

    protected function startTime() : void {
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

    public function setClosure(?Closure $closure) : void {
        if ($closure !== null) {
            $this->storeLocal("callback", $closure);
            $this->hasCallback = true;
        } else {
            $this->hasCallback = false;
        }
    }

    public function onCompletion() : void {
        if ($this->hasCallback) {
            $this->fetchLocal("callback")($this->getElapsedTime(), $this->getElapsedTimeString(), $this->getResult());
        }
    }
}