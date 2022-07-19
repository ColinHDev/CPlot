<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BlockListAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use pocketmine\block\Block;

/**
 * @phpstan-implements Flag<Block[]>
 */
class BreakFlag extends BlockListAttribute implements Flag {

    final public function __construct(array $value) {
        parent::__construct(FlagIDs::FLAG_BREAK, $value);
    }

    public static function NONE() : static {
        return new static([]);
    }

    public function createInstance(mixed $value) : static {
        return new static($value);
    }
}