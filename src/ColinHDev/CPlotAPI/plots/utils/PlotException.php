<?php

namespace ColinHDev\CPlotAPI\plots\utils;

use ColinHDev\CPlotAPI\plots\BasePlot;
use Throwable;

class PlotException extends \Exception {

    private BasePlot $plot;

    public function __construct(BasePlot $plot, $message = "", $code = 0, Throwable $previous = null) {
        $this->plot = $plot;
        parent::__construct($message, $code, $previous);
    }

    public function getPlot() : BasePlot {
        return $this->plot;
    }
}