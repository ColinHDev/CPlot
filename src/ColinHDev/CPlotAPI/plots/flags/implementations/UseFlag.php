<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BlockListFlag;
use pocketmine\block\Block;

/**
 * @extends BlockListFlag<UseFlag, array<int, Block>>
 */
class UseFlag extends BlockListFlag {

    protected static string $ID = self::FLAG_USE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_USE;
    protected static string $default;
}