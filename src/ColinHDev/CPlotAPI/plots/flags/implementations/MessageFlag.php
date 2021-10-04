<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\StringFlag;

/**
 * @extends StringFlag<MessageFlag, string>
 */
class MessageFlag extends StringFlag {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public function flagOf(mixed $value) : MessageFlag {
        return new self($value);
    }
}