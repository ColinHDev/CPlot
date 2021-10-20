<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<TeleportItemPickupFlagChangeSetting, bool>
 */
class TeleportItemPickupFlagChangeSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP;
    protected static string $default;
}