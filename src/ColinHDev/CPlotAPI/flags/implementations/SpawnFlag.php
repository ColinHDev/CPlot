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
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return $data->getX() . ";" . $data->getY() . ";" . $data->getZ();
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
        [$x, $y, $z] = explode(";", $serializedValue);
        return new Vector3((float) $x, (float) $y, (float) $z);
    }

    /**
     * @return array
     */
    public function __serialize() : array {
        $data = parent::__serialize();
        $data["value"] = $this->serializeValueType($this->value);
        return $data;
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->value = $this->unserializeValueType($data["value"]);
    }
}