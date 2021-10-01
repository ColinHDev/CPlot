<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<UseFlag, array<int, Block>>
 */
class UseFlag extends BlockListFlag {

    public function flagOf(mixed $value) : UseFlag {
        return new self($value);
    }
}