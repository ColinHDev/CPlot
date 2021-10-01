<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<BurningFlag, bool>
 */
class BurningFlag extends BooleanFlag {

    public function flagOf(mixed $value) : BurningFlag {
        return new self($value);
    }
}