<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<InformDeniedAddSetting, bool>
 */
class InformDeniedAddSetting extends BooleanAttribute implements Setting {

    protected static string $ID = self::SETTING_INFORM_DENIED_ADD;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_INFORM_DENIED_ADD;
    protected static string $default;
}