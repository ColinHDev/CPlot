<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotEnterFlag, bool>
 */
class PlotEnterFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_PLOT_ENTER;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLOT_ENTER;
    protected static string $default;
}