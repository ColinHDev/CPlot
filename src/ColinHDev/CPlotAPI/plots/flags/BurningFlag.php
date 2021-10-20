<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<BurningFlag, bool>
 */
class BurningFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_BURNING;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_BURNING;
    protected static string $default;
}