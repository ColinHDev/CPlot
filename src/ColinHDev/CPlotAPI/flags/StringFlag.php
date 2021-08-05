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
     * @return string
     */
    public function serializeValue() : string {
        return $this->value;
    }

    /**
     * @param string $serializedValue
     */
    public function unserializeValue(string $serializedValue) : void {
        $this->value = $serializedValue;
    }
}