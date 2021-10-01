<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ServerPlotFlag, bool>
 */
class ServerPlotFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ServerPlotFlag {
        return new self($value);
    }
}