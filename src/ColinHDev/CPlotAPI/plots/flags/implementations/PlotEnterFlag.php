<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotEnterFlag, bool>
 */
class PlotEnterFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlotEnterFlag {
        return new self($value);
    }
}