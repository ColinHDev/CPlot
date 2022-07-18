<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BlockListAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use pocketmine\block\Block;

/**
 * @phpstan-extends BlockListAttribute<BreakFlag>
 * @phpstan-implements Flag<BreakFlag, Block[]>
 */
class BreakFlag extends BlockListAttribute implements Flag {

    public function __construct(array $value) {
        parent::__construct(FlagIDs::FLAG_BREAK, $value);
    }

    public static function NONE() : self {
        return new self([]);
    }

    public function createInstance(mixed $value) : self {
        return new self($value);
    }
}