<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PveFlag, bool>
 */
class PveFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_PVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PVE;
    protected static string $default;
}