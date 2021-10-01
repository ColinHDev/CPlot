<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotEnterFlag, bool>
 */
class PlotEnterFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlotEnterFlag {
        return new self($value);
    }
}