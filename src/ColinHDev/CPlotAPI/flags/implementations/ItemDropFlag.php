<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemDropFlag, bool>
 */
class ItemDropFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ItemDropFlag {
        return new self($value);
    }
}