<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<PvpFlag, bool>
 */
class PvpFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_PVP;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PVP;
    protected static string $default;
}