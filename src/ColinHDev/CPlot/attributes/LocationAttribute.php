<?php

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use pocketmine\entity\Location;

class LocationAttribute extends BaseAttribute {

    /**
     * @param Location $value
     */
    public function merge(mixed $value) : LocationAttribute {
        return $this->newInstance($value);
    }

    /**
     * @param Location | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return $value->getX() . ";" . $value->getY() . ";" . $value->getZ() . ";" . $value->getYaw() . ";" . $value->getPitch();
    }

    /**
     * @throws AttributeParseException
     */
    public function parse(string $value) : Location {
        [$x, $y, $z, $yaw, $pitch] = explode(";", $value);
        if ($x !== null && $y !== null && $z !== null && $yaw !== null && $pitch !== null) {
            return new Location((float) $x, (float) $y, (float) $z, null, (float) $yaw, (float) $pitch);
        }
        throw new AttributeParseException($this, $value);
    }
}