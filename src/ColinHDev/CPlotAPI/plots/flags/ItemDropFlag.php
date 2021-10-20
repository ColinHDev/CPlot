<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<ItemDropFlag, bool>
 */
class ItemDropFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_ITEM_DROP;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_ITEM_DROP;
    protected static string $default;
}