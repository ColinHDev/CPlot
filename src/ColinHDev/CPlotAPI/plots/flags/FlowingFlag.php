<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<FlowingFlag, bool>
 */
class FlowingFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_FLOWING;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_FLOWING;
    protected static string $default;
}