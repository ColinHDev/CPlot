<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\tasks\utils\AsyncTaskResult;
use ColinHDev\CPlot\utils\exception\AsyncTaskException;
use InvalidArgumentException;
use pocketmine\scheduler\AsyncTask;
use Throwable;
use function microtime;
use function round;

abstract class CPlotAsyncTask extends AsyncTask {

    public function __construct() {
        $this->storeLocal("startTime", ((int) (round(microtime(true) * 1000))));
    }

    /**
     * @param null|callable(AsyncTaskResult): void) $onSuccess
     * @param null|callable(Throwable): void $onError
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
            /** @var callable(AsyncTaskResult): void $callback */
            $callback = $this->fetchLocal("onSuccess");
            /** @var int $startTime */
            $startTime = $this->fetchLocal("startTime");
            $callback(
                new AsyncTaskResult(
                    ((int) (round(microtime(true) * 1000))) - $startTime,
                    $this->getResult()
                )
            );
        } catch (InvalidArgumentException) {
        }
    }

    public function onError() : void {
        try {
            /** @var callable(Throwable): void $callback */
            $callback = $this->fetchLocal("onError");
            $callback(
                new AsyncTaskException(
                    "An error occurred while executing the async task."
                )
            );
        } catch (InvalidArgumentException) {
        }
    }
}