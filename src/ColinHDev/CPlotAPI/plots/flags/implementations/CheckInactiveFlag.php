<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<CheckInactiveFlag, bool>
 */
class CheckInactiveFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_CHECK_INACTIVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_CHECK_INACTIVE;
    protected static string $default;
}