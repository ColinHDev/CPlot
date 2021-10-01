<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<PlaceFlag, array<int, Block>>
 */
class PlaceFlag extends BlockListFlag {

    public function flagOf(mixed $value) : PlaceFlag {
        return new self($value);
    }
}