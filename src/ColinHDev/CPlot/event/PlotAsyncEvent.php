<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\libAsyncEvent\AsyncEvent;
use ColinHDev\libAsyncEvent\ConsecutiveEventHandlerExecutionTrait;
use pocketmine\event\Event;

/**
 * @link https://github.com/ColinHDev/libAsyncEvent/
 * @method void block()
 * @method void release()
 */
abstract class PlotAsyncEvent extends Event implements AsyncEvent {
    use ConsecutiveEventHandlerExecutionTrait;

    private Plot $plot;

    public function __construct(Plot $plot) {
        $this->plot = $plot;
    }

    public function getPlot() : Plot {
        return $this->plot;
    }
}