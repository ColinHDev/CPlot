<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use pocketmine\entity\Location;

/**
 * @extends BaseAttribute<SpawnFlag, Location>
 */
class SpawnFlag extends BaseAttribute implements Flag {

    protected static string $ID = self::FLAG_SPAWN;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_SPAWN;
    protected static string $default;

    /**
     * @param Location $value
     */
    public function merge(mixed $value) : SpawnFlag {
        return new static($value);
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