<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<FlowingFlag, bool>
 */
class FlowingFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_FLOWING;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_FLOWING;
    protected static string $default;
}