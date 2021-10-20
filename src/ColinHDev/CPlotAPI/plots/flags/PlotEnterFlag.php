<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<PlotEnterFlag, bool>
 */
class PlotEnterFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_PLOT_ENTER;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLOT_ENTER;
    protected static string $default;
}