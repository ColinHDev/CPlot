<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\libAsyncEvent\AsyncEvent;

abstract class PlotAsyncEvent extends AsyncEvent {

    private Plot $plot;

    public function __construct(Plot $plot) {
        $this->plot = $plot;
    }

    public function getPlot() : Plot {
        return $this->plot;
    }
}