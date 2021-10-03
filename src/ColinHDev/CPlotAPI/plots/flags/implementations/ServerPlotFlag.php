<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ServerPlotFlag, bool>
 */
class ServerPlotFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ServerPlotFlag {
        return new self($value);
    }
}