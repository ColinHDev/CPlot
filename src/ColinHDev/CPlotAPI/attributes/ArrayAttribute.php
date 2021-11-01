<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use ColinHDev\CPlotAPI\attributes\utils\AttributeTypeException;

/**
 * @template AttributeType of ArrayAttribute
 * @extends BaseAttribute<AttributeType, array>
 */
abstract class ArrayAttribute extends BaseAttribute {

    /**
     * @param array $value
     * @return AttributeType
     * @throws AttributeTypeException
     */
    public function merge(mixed $value) : ArrayAttribute {
        $values = $this->value;
        foreach ($value as $newValue) {
            $newValueString = $this->toString([$newValue]);
            foreach ($values as $oldValue) {
                if ($this->toString([$oldValue]) === $newValueString) {
                    continue 2;
                }
            }
            $values[] = $newValue;
        }
        return new static($values);
    }

    /**
     * @param array | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return json_encode($value);
    }

    public function parse(string $value) : array {
        $parsed = json_decode($value, true);
        if (is_array($parsed)) {
            return $parsed;
        }
        throw new AttributeParseException($this, $value);
    }
}