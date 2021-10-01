<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotLeaveFlag, bool>
 */
class PlotLeaveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlotLeaveFlag {
        return new self($value);
    }
}