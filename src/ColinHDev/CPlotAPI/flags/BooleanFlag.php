<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;

class BooleanFlag extends BaseFlag {

    protected bool $default;
    protected ?bool $value = null;

    /**
     * BooleanFlag constructor.
     * @param string    $ID
     * @param array     $data
     */
    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (bool) $data["standard"];
    }

    /**
     * @return bool
     */
    public function getDefault() : bool {
        return $this->default;
    }

    /**
     * @return bool | null
     */
    public function getValue() : ?bool {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function getValueNonNull() : bool {
        if ($this->value !== null) {
            return $this->value;
        }
        return $this->default;
    }

    /**
     * @param mixed $value
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!is_bool($value)) {
                throw new InvalidValueException("Expected value to be boolean or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return $data ? "true" : "false";
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
         if ($serializedValue === "true") return true;
         return false;
    }

    /**
     * @return array
     */
    public function __serialize() : array {
        $data = parent::__serialize();
        $data["default"] = $this->serializeValueType($this->default);
        $data["value"] = $this->serializeValueType($this->value);
        return $data;
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->default = $this->unserializeValueType($data["default"]);
        $this->value = $this->unserializeValueType($data["value"]);
    }
}