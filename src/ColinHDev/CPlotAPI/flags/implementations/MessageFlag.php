<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\StringFlag;

/**
 * @extends StringFlag<MessageFlag, string>
 */
class MessageFlag extends StringFlag {

    public function flagOf(mixed $value) : MessageFlag {
        return new self($value);
    }
}