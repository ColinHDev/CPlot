<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<TitleFlag, bool>
 */
class TitleFlag extends BooleanFlag {

    public function flagOf(mixed $value) : TitleFlag {
        return new self($value);
    }
}