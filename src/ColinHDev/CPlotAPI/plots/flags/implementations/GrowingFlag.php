<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<GrowingFlag, bool>
 */
class GrowingFlag extends BooleanFlag {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public function flagOf(mixed $value) : GrowingFlag {
        return new self($value);
    }
}