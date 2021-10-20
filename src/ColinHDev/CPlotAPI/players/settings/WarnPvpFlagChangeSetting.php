<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanListAttribute;

/**
 * @extends BooleanListAttribute<WarnPvpFlagChangeSetting, bool>
 */
class WarnPvpFlagChangeSetting extends BooleanListAttribute implements Setting {

    protected static string $ID = self::SETTING_WARN_CHANGE_FLAG_PVP;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_WARN_CHANGE_FLAG_PVP;
    protected static string $default;
}