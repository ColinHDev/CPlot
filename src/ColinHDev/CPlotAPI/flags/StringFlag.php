<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;

class StringFlag extends BaseFlag {

    protected string $default;
    protected ?string $value = null;

    /**
     * StringFlag constructor.
     * @param string    $ID
     * @param array     $data
     */
    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (string) $data["standard"];
    }

    /**
     * @return string
     */
    public function getDefault() : string {
        return $this->default;
    }

    /**
     * @return string | null
     */
    public function getValue() : ?string {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getValueNonNull() : string {
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
            if (!is_string($value)) {
                throw new InvalidValueException("Expected value to be string or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return $data;
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
        return $serializedValue;
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