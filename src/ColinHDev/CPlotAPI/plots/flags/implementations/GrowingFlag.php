<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<GrowingFlag, bool>
 */
class GrowingFlag extends BooleanFlag {

    public function flagOf(mixed $value) : GrowingFlag {
        return new self($value);
    }
}