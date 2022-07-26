<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use pocketmine\entity\Location;
use function explode;
use function is_infinite;
use function is_nan;
use function is_numeric;

/**
 * @extends BaseAttribute<Location>
 */
abstract class LocationAttribute extends BaseAttribute {

    public function equals(object $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value->equals($other->getValue());
    }

    /**
     * @param Location $value
     */
    public function contains(mixed $value) : bool {
        return $this->equals($this->createInstance($value));
    }

    /**
     * @param Location $value
     */
    public function merge(mixed $value) : self {
        return $this->createInstance($value);
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
        if (is_numeric($x) && is_numeric($y) && is_numeric($z) && is_numeric($yaw) && is_numeric($pitch)) {
            $x = (float) $x;
            $y = (float) $y;
            $z = (float) $z;
            $yaw = (float) $yaw;
            $pitch = (float) $pitch;
            if (
                !is_nan($x) && !is_nan($y) && !is_nan($z) && !is_nan($yaw) && !is_nan($pitch) &&
                !is_infinite($x) && !is_infinite($y) && !is_infinite($z) && !is_infinite($yaw) && !is_infinite($pitch)
            ) {
                return new Location($x, $y, $z, null, $yaw, $pitch);
            }
        }
        throw new AttributeParseException($this, $value);
    }
}