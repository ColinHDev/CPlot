<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ServerPlotFlag, bool>
 */
class ServerPlotFlag extends BooleanFlag {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public function flagOf(mixed $value) : ServerPlotFlag {
        return new self($value);
    }
}