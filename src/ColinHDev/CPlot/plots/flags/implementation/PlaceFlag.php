<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BlockListAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use pocketmine\block\Block;

/**
 * @phpstan-extends BlockListAttribute<PlaceFlag>
 * @phpstan-implements Flag<PlaceFlag, Block[]>
 */
class PlaceFlag extends BlockListAttribute implements Flag {

    public function __construct(array $value) {
        parent::__construct(FlagIDs::FLAG_PLACE, $value);
    }

    public static function NONE() : self {
        return new self([]);
    }

    public function createInstance(mixed $value) : self {
        return new self($value);
    }
}