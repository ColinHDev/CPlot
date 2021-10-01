<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<CheckInactiveFlag, bool>
 */
class CheckInactiveFlag extends BooleanFlag {

    public function flagOf(mixed $value) : CheckInactiveFlag {
        return new self($value);
    }
}