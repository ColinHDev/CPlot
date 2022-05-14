<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

    /**
     * @phpstan-param (callable(): void)|null $onSuccess
     * @phpstan-param (callable(): void)|null $onError
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
            /** @phpstan-var callable(): void $callback */
            $callback = $this->fetchLocal("onSuccess");
            $callback();
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