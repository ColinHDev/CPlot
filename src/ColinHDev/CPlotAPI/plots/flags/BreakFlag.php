<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BlockListAttribute;
use pocketmine\block\Block;

/**
 * @extends BlockListAttribute<BreakFlag, array<int, Block>>
 */
class BreakFlag extends BlockListAttribute implements Flag {

    protected static string $ID = self::FLAG_BREAK;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_BREAK;
    protected static string $default;
}