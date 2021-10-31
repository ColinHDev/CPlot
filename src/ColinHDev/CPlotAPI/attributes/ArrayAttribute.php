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
        return new static(array_merge($this->value, $value));
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