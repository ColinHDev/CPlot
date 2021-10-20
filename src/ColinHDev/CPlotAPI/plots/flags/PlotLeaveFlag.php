<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<PlotLeaveFlag, bool>
 */
class PlotLeaveFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_PLOT_LEAVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLOT_LEAVE;
    protected static string $default;
}