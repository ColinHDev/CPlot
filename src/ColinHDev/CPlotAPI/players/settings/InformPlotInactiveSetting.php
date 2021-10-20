<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<InformPlotInactiveSetting, bool>
 */
class InformPlotInactiveSetting extends BooleanAttribute implements Setting {

    protected static string $ID = self::SETTING_INFORM_PLOT_INACTIVE;
    protected static string $permission = self::PERMISSION_BASE . self::SETTING_INFORM_PLOT_INACTIVE;
    protected static string $default;
}