<?php

namespace ColinHDev\CPlot\player\settings\implementation;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingIDs;

/**
 * @phpstan-implements Setting<bool>
 */
class InformUndeniedSetting extends BooleanAttribute implements Setting {

    final public function __construct(bool $value) {
        parent::__construct(SettingIDs::SETTING_INFORM_UNDENIED, $value);
    }

    public static function TRUE() : static {
        return new static(true);
    }

    public static function FALSE() : static {
        return new static(false);
    }

    public function createInstance(mixed $value) : static {
        return $value === true ? self::TRUE() : self::FALSE();
    }
}