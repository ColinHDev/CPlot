<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<ExplosionFlag, bool>
 */
class ExplosionFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_EXPLOSION;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_EXPLOSION;
    protected static string $default;
}