<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<BreakFlag, array<int, Block>>
 */
class BreakFlag extends BlockListFlag {

    protected static string $ID = self::FLAG_BREAK;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_BREAK;
    protected static string $default;
}