<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ExplosionFlag, bool>
 */
class ExplosionFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ExplosionFlag {
        return new self($value);
    }
}