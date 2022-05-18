<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Event;

abstract class PlotEvent extends Event {

    private Plot $plot;

    public function __construct(Plot $plot) {
        $this->plot = $plot;
    }

    public function getPlot() : Plot {
        return $this->plot;
    }
}