<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings\implementation;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\FlagListAttribute;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\Flag;

/**
 * @phpstan-implements Setting<array<BaseAttribute<mixed>&Flag<mixed>>>
 */
class WarnFlagSetting extends FlagListAttribute implements Setting {

    final public function __construct(array $value) {
        parent::__construct(SettingIDs::SETTING_WARN_FLAG, $value);
    }

    public static function NONE() : self {
        return new self([]);
    }

    public function createInstance(mixed $value) : static {
        return new static($value);
    }
}