<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PvpFlag, bool>
 */
class PvpFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PvpFlag {
        return new self($value);
    }
}