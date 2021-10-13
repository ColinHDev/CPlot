<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<PlayerInteractFlag, bool>
 */
class PlayerInteractFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_PLAYER_INTERACT;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLAYER_INTERACT;
    protected static string $default;
}