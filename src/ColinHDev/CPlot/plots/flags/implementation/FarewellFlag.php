<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\StringAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;

/**
 * @phpstan-extends StringAttribute<FarewellFlag>
 * @phpstan-implements Flag<FarewellFlag, string>
 */
class FarewellFlag extends StringAttribute implements Flag {

    public function __construct(string $value) {
        parent::__construct(FlagIDs::FLAG_FAREWELL, $value);
    }

    public static function EMPTY() : self {
        return new self("");
    }

    public function createInstance(mixed $value) : self {
        return new self($value);
    }
}