<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<BreakFlag, array<int, Block>>
 */
class BreakFlag extends BlockListFlag {

    public function flagOf(mixed $value) : BreakFlag {
        return new self($value);
    }
}