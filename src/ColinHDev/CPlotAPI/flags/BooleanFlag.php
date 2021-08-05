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
     * @return string
     */
    public function serializeValue() : string {
        return $this->value ? "true" : "false";
    }

    /**
     * @param string $serializedValue
     */
    public function unserializeValue(string $serializedValue) : void {
        $this->value = $serializedValue;
    }
}