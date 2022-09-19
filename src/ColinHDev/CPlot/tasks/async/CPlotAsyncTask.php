<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

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