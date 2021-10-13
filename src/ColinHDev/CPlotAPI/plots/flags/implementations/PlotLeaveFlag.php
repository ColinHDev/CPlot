<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlotLeaveFlag, bool>
 */
class PlotLeaveFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_PLOT_LEAVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLOT_LEAVE;
    protected static string $default;
}