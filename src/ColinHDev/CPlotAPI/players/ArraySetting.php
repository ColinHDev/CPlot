<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;

class ArraySetting extends BaseSetting {

    protected array $default;
    protected ?array $value = null;

    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (array) $data["default"];
    }

    public function getDefault() : array {
        return $this->default;
    }

    public function getValue() : ?array {
        return $this->value;
    }

    public function getValueNonNull() : array {
        if ($this->value !== null) {
            return $this->value;
        }
        return $this->default;
    }

    /**
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


    public function serializeValueType(mixed $data) : string {
        return implode(";", $data);
    }

    public function unserializeValueType(string $serializedValue) : array {
        if ($serializedValue === "") {
            $data = [];
        } else {
            $data = explode(";", $serializedValue);
        }
        return $data;
    }
}