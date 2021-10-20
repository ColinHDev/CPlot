<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<PveFlag, bool>
 */
class PveFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_PVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PVE;
    protected static string $default;
}