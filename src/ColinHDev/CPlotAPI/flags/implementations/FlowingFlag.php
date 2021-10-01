<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<FlowingFlag, bool>
 */
class FlowingFlag extends BooleanFlag {

    public function flagOf(mixed $value) : FlowingFlag {
        return new self($value);
    }
}