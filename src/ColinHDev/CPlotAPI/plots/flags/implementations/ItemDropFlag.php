<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemDropFlag, bool>
 */
class ItemDropFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ItemDropFlag {
        return new self($value);
    }
}