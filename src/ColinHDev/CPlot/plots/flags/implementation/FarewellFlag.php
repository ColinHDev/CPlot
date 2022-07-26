<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\StringAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;

/**
 * @implements Flag<string>
 */
class FarewellFlag extends StringAttribute implements Flag {

    final public function __construct(string $value) {
        parent::__construct(FlagIDs::FLAG_FAREWELL, $value);
    }

    public static function EMPTY() : static {
        return new static("");
    }

    public function createInstance(mixed $value) : static {
        return new static($value);
    }
}