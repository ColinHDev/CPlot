<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<InformHelperAddSetting, bool>
 */
class InformHelperAddSetting extends BooleanAttribute implements Setting {

    protected static string $ID = self::SETTING_INFORM_HELPER_ADD;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_INFORM_HELPER_ADD;
    protected static string $default;
}