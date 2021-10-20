<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BlockListAttribute;
use pocketmine\block\Block;

/**
 * @extends BlockListAttribute<PlaceFlag, array<int, Block>>
 */
class PlaceFlag extends BlockListAttribute implements Flag {

    protected static string $ID = self::FLAG_PLACE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLACE;
    protected static string $default;
}