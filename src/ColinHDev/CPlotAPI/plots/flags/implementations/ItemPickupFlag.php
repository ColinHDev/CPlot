<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemPickupFlag, bool>
 */
class ItemPickupFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_ITEM_PICKUP;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_ITEM_PICKUP;
    protected static string $default;
}