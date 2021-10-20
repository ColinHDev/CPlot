<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<InformTrustedAddSetting, bool>
 */
class InformTrustedAddSetting extends BooleanAttribute implements Setting {

    protected static string $ID = self::SETTING_INFORM_TRUSTED_ADD;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_INFORM_TRUSTED_ADD;
    protected static string $default;
}