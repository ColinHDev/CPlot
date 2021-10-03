<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<CheckInactiveFlag, bool>
 */
class CheckInactiveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : CheckInactiveFlag {
        return new self($value);
    }
}