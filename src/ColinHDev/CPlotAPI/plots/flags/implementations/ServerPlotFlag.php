<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ServerPlotFlag, bool>
 */
class ServerPlotFlag extends BooleanFlag {

    protected static string $ID = self::FLAG_SERVER_PLOT;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_SERVER_PLOT;
    protected static string $default;
}