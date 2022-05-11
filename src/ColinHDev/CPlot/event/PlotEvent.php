<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;

abstract class PlotEvent extends CPlotAsyncEvent {

    private Plot $plot;

    public function __construct(Plot $plot) {
        $this->plot = $plot;
    }

    public function getPlot() : Plot {
        return $this->plot;
    }
}