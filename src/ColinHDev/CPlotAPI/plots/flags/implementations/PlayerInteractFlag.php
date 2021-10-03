<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlayerInteractFlag, bool>
 */
class PlayerInteractFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlayerInteractFlag {
        return new self($value);
    }
}