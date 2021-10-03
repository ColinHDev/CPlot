<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<TitleFlag, bool>
 */
class TitleFlag extends BooleanFlag {

    public function flagOf(mixed $value) : TitleFlag {
        return new self($value);
    }
}