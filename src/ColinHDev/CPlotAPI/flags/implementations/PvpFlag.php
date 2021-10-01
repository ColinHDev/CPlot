<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PvpFlag, bool>
 */
class PvpFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PvpFlag {
        return new self($value);
    }
}