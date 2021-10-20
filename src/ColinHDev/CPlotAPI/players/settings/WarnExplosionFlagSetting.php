<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<WarnExplosionFlagSetting, bool>
 */
class WarnExplosionFlagSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_WARN_FLAG_EXPLOSION;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_WARN_FLAG_EXPLOSION;
    protected static string $default;
}