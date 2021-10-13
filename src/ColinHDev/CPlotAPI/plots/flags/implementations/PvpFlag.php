<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PvpFlag, bool>
 */
class PvpFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_PVP;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PVP;
    protected static string $default;
}