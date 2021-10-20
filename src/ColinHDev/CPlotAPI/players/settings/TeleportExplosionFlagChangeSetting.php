<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<TeleportExplosionFlagChangeSetting, bool>
 */
class TeleportExplosionFlagChangeSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION;
    protected static string $default;
}