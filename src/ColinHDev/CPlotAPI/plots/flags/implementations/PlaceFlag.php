<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<PlaceFlag, array<int, Block>>
 */
class PlaceFlag extends BlockListFlag {

    protected static string $ID = self::FLAG_PLACE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLACE;
    protected static string $default;
}