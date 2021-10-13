<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<GrowingFlag, bool>
 */
class GrowingFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_GROWING;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_GROWING;
    protected static string $default;
}