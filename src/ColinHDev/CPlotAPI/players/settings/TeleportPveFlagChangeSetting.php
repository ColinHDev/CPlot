<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<TeleportPveFlagChangeSetting, bool>
 */
class TeleportPveFlagChangeSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_TELEPORT_CHANGE_FLAG_PVE;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_TELEPORT_CHANGE_FLAG_PVE;
    protected static string $default;
}