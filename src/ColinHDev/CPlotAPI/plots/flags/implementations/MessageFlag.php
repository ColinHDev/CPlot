<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\StringFlag;

/**
 * @extends StringFlag<MessageFlag, string>
 */
class MessageFlag extends StringFlag {

    public function flagOf(mixed $value) : MessageFlag {
        return new self($value);
    }
}