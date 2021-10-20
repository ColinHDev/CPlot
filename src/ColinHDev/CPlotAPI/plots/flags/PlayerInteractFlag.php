<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<PlayerInteractFlag, bool>
 */
class PlayerInteractFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_PLAYER_INTERACT;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_PLAYER_INTERACT;
    protected static string $default;
}