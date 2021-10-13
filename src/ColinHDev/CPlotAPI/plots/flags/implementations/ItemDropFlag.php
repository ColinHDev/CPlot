<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemDropFlag, bool>
 */
class ItemDropFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_ITEM_DROP;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_ITEM_DROP;
    protected static string $default;
}