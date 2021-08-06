<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use pocketmine\math\Vector3;

class SpawnFlag extends BaseFlag {

    protected ?Vector3 $value = null;

    public function getDefault() : mixed {
        return null;
    }

    /**
     * @return Vector3 | null
     */
    public function getValue() : ?Vector3 {
        return $this->value;
    }

    /**
     * @return Vector3 | null
     */
    public function getValueNonNull() : ?Vector3 {
        return $this->getValue();
    }

    /**
     * @param mixed $value
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!$value instanceof Vector3) {
                throw new InvalidValueException("Expected value to be instance of Vector3 or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function serializeValue() : string {
        return $this->value->getX() . ";" . $this->value->getY() . ";" . $this->value->getZ();
    }

    /**
     * @param string $serializedValue
     */
    public function unserializeValue(string $serializedValue) : void {
        [$x, $y, $z] = explode(";", $serializedValue);
        $this->value = new Vector3((float) $x, (float) $y, (float) $z);
    }
}