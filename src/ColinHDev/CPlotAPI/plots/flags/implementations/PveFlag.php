<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PveFlag, bool>
 */
class PveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PveFlag {
        return new self($value);
    }
}