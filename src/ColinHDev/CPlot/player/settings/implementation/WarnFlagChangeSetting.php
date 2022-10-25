<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings\implementation;

use ColinHDev\CPlot\attributes\FlagListAttribute;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\Flag;

/**
 * @implements Setting<array<Flag<mixed>>>
 */
class WarnFlagChangeSetting extends FlagListAttribute implements Setting {

    final public function __construct(array $value) {
        parent::__construct(SettingIDs::SETTING_WARN_FLAG_CHANGE, $value);
    }

    public static function NONE() : static {
        return new static([]);
    }

    public function createInstance(mixed $value) : static {
        return new static($value);
    }
}