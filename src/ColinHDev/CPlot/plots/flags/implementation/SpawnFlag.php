<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\LocationAttribute;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use pocketmine\entity\Location;

/**
 * @phpstan-implements Flag<Location>
 */
class SpawnFlag extends LocationAttribute implements Flag {

    public function __construct(Location $value) {
        parent::__construct(FlagIDs::FLAG_SPAWN, $value);
    }

    public static function NONE() : self {
        return new self(new Location(0.0, 0.0, 0.0, null, 0.0, 0.0));
    }

    /**
     * @phpstan-param Location $value
     */
    public function createInstance(mixed $value) : self {
        return new self($value);
    }
}