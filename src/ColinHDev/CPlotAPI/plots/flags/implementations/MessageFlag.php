<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\StringFlag;

/**
 * @extends StringFlag<MessageFlag, string>
 */
class MessageFlag extends StringFlag {

    protected static string $ID = self::FLAG_MESSAGE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_MESSAGE;
    protected static string $default;
}