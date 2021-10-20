<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<WarnPvpFlagSetting, bool>
 */
class WarnPvpFlagSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_WARN_FLAG_PVP;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_WARN_FLAG_PVP;
    protected static string $default;
}