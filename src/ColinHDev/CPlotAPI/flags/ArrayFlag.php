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
        $this->default = (array) $data["default"];
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
     * @return string
     */
    public function serializeValue() : string {
        return implode(";", $this->value);
    }

    /**
     * @param string $serializedValue
     */
    public function unserializeValue(string $serializedValue) : void {
        if ($serializedValue === "") {
            $this->value = [];
        } else {
            $this->value = explode(";", $serializedValue);
        }
    }
}