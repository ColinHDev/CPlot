<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BlockListAttribute;
use pocketmine\block\Block;

/**
 * @extends BlockListAttribute<UseFlag, array<int, Block>>
 */
class UseFlag extends BlockListAttribute implements Flag {

    protected static string $ID = self::FLAG_USE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_USE;
    protected static string $default;
}