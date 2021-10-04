<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotEnterFlag, bool>
 */
class PlotEnterFlag extends BooleanFlag {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public function flagOf(mixed $value) : PlotEnterFlag {
        return new self($value);
    }
}