<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<FlowingFlag, bool>
 */
class FlowingFlag extends BooleanFlag {

    public function flagOf(mixed $value) : FlowingFlag {
        return new self($value);
    }
}