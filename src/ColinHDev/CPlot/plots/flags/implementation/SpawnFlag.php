<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\LocationAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\flags\InternalFlag;
use pocketmine\entity\Location;

/**
 * @implements Flag<Location>
 */
class SpawnFlag extends LocationAttribute implements Flag, InternalFlag {

    final public function __construct(Location $value) {
        parent::__construct(FlagIDs::FLAG_SPAWN, $value);
    }

    public static function NONE() : static {
        return new static(new Location(0.0, 0.0, 0.0, null, 0.0, 0.0));
    }

    public function createInstance(mixed $value) : static {
        return new static($value);
    }
}