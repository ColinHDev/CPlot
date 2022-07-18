<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;

/**
 * @phpstan-extends BooleanAttribute<ItemDropFlag>
 * @phpstan-implements Flag<ItemDropFlag, bool>
 */
class ItemDropFlag extends BooleanAttribute implements Flag {

    public function __construct(bool $value) {
        parent::__construct(FlagIDs::FLAG_ITEM_DROP, $value);
    }

    public static function TRUE() : self {
        return new self(true);
    }

    public static function FALSE() : self {
        return new self(false);
    }

    public function createInstance(mixed $value) : self {
        return $value === true ? self::TRUE() : self::FALSE();
    }
}