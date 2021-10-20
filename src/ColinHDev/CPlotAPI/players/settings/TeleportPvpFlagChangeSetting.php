<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<TeleportPvpFlagChangeSetting, bool>
 */
class TeleportPvpFlagChangeSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_TELEPORT_CHANGE_FLAG_PVP;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_TELEPORT_CHANGE_FLAG_PVP;
    protected static string $default;
}