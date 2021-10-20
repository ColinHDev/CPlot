<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<CheckInactiveFlag, bool>
 */
class CheckInactiveFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_CHECK_INACTIVE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_CHECK_INACTIVE;
    protected static string $default;
}