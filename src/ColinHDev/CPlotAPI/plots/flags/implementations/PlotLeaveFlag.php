<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotLeaveFlag, bool>
 */
class PlotLeaveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlotLeaveFlag {
        return new self($value);
    }
}