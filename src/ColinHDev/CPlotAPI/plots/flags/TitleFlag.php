<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<TitleFlag, bool>
 */
class TitleFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_TITLE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_TITLE;
    protected static string $default;
}