<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemPickupFlag, bool>
 */
class ItemPickupFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ItemPickupFlag {
        return new self($value);
    }
}