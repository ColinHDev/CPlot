<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<InformHelperRemoveSetting, bool>
 */
class InformHelperRemoveSetting extends BooleanAttribute implements Setting {

    protected static string $ID = self::SETTING_INFORM_HELPER_REMOVE;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_INFORM_HELPER_REMOVE;
    protected static string $default;
}