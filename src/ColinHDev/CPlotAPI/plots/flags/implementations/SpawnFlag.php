<?php

namespace ColinHDev\CPlotAPI\plots\flags\implementations;

use ColinHDev\CPlotAPI\plots\flags\BaseFlag;
use ColinHDev\CPlotAPI\plots\flags\utils\FlagParseException;
use pocketmine\entity\Location;

/**
 * @extends BaseFlag<SpawnFlag, Location>
 */
class SpawnFlag extends BaseFlag {

    protected Location $value;

    public function __construct(mixed $value) {
        $this->value = $value;
    }

    public function getValue() : Location {
        return $this->value;
    }

    public function flagOf(mixed $value) : SpawnFlag {
        return new self($value);
    }

    /**
     * @param Location $value
     * @return SpawnFlag
     */
    public function merge(mixed $value) : SpawnFlag {
        return $this->flagOf($value);
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
     * @throws FlagParseException
     */
    public function parse(string $value) : Location {
        [$x, $y, $z, $yaw, $pitch] = explode(";", $value);
        if ($x !== null && $y !== null && $z !== null && $yaw !== null && $pitch !== null) {
            return new Location((float) $x, (float) $y, (float) $z, (float) $yaw, (float) $pitch);
        }
        throw new FlagParseException($this, $value);
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["value"] = $this->toString();
        return $data;
    }

    /**
     * @throws FlagParseException
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->value = $this->parse($data["value"]);
    }
}