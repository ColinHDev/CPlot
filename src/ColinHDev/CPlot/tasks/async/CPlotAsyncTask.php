<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use pocketmine\scheduler\AsyncTask;

abstract class CPlotAsyncTask extends AsyncTask {

    /**
     * @phpstan-param callable(): void $onSuccess
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