<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PveFlag, bool>
 */
class PveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PveFlag {
        return new self($value);
    }
}