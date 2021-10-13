<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ExplosionFlag, bool>
 */
class ExplosionFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_EXPLOSION;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_EXPLOSION;
    protected static string $default;
}