<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BooleanAttribute;

/**
 * @extends BooleanAttribute<ServerPlotFlag, bool>
 */
class ServerPlotFlag extends BooleanAttribute implements Flag {

    protected static string $ID = self::FLAG_SERVER_PLOT;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_SERVER_PLOT;
    protected static string $default;
}