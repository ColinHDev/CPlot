<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<TitleFlag, bool>
 */
class TitleFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_TITLE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_TITLE;
    protected static string $default;
}