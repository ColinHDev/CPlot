<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlayerInteractFlag, bool>
 */
class PlayerInteractFlag extends BooleanFlag {

    public function flagOf(mixed $value) : PlayerInteractFlag {
        return new self($value);
    }
}