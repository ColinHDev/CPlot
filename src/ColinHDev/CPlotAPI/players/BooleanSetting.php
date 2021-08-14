<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;

class BooleanSetting extends BaseSetting {

    protected bool $default;
    protected ?bool $value = null;

    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
        $this->default = (bool) $data["standard"];
    }

    public function getDefault() : bool {
        return $this->default;
    }

    public function getValue() : ?bool {
        return $this->value;
    }

    public function getValueNonNull() : bool {
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
            if (!is_bool($value)) {
                throw new InvalidValueException("Expected value to be boolean or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }


    public function serializeValueType(mixed $data) : string {
        return $data ? "true" : "false";
    }

    public function unserializeValueType(string $serializedValue) : bool {
        if ($serializedValue === "true") return true;
        return false;
    }
}