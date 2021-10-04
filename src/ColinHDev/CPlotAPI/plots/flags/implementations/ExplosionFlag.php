<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ExplosionFlag, bool>
 */
class ExplosionFlag extends BooleanFlag {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public function flagOf(mixed $value) : ExplosionFlag {
        return new self($value);
    }
}