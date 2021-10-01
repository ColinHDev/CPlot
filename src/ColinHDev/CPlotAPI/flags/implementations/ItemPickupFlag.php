<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BooleanFlag;

/**
 * @extends BooleanFlag<ItemPickupFlag, bool>
 */
class ItemPickupFlag extends BooleanFlag {

    public function flagOf(mixed $value) : ItemPickupFlag {
        return new self($value);
    }
}