<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<GrowingFlag, bool>
 */
class GrowingFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_GROWING;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_GROWING;
    protected static string $default;
}