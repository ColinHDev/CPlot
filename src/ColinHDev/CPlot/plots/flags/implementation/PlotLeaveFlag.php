<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;

/**
 * @phpstan-implements Flag<bool>
 */
class PlotLeaveFlag extends BooleanAttribute implements Flag {

    final public function __construct(bool $value) {
        parent::__construct(FlagIDs::FLAG_PLOT_LEAVE, $value);
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