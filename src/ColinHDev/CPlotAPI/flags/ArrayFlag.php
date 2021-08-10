<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;

class ArrayFlag extends BaseFlag {

    protected array $default;
    protected ?array $value = null;

    /**
     * ArrayFlag constructor.
     * @param string    $ID
     * @param array     $data
     */
    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (array) $data["standard"];
    }

    /**
     * @return array
     */
    public function getDefault() : array {
        return $this->default;
    }

    /**
     * @return array | null
     */
    public function getValue() : ?array {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getValueNonNull() : array {
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
            if (!is_array($value)) {
                throw new InvalidValueException("Expected value to be array or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return implode(";", $data);
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
        if ($serializedValue === "") {
            $data = [];
        } else {
            $data = explode(";", $serializedValue);
        }
        return $data;
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