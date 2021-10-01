<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<GrowingFlag, bool>
 */
class GrowingFlag extends BooleanFlag {

    public function flagOf(mixed $value) : GrowingFlag {
        return new self($value);
    }
}