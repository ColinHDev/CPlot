<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<BurningFlag, bool>
 */
class BurningFlag extends BooleanFlag {

    public function flagOf(mixed $value) : BurningFlag {
        return new self($value);
    }
}