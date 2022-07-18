<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use pocketmine\entity\Location;

/**
 * @phpstan-template TAttributeType of LocationAttribute
 * @phpstan-extends BaseAttribute<TAttributeType, Location>
 */
abstract class LocationAttribute extends BaseAttribute {

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value->equals($other->getValue());
    }

    public function merge(mixed $value) : BaseAttribute {
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
        /** @phpstan-var string|null $x */
        /** @phpstan-var string|null $y */
        /** @phpstan-var string|null $z */
        /** @phpstan-var string|null $yaw */
        /** @phpstan-var string|null $pitch */
        [$x, $y, $z, $yaw, $pitch] = explode(";", $value);
        if (isset($x, $y, $z, $yaw, $pitch)) {
            return new Location((float) $x, (float) $y, (float) $z, null, (float) $yaw, (float) $pitch);
        }
        throw new AttributeParseException($this, $value);
    }
}