<?php

namespace ColinHDev\CPlotAPI\attributes;

/**
 * @template AttributeType of ArrayAttribute
 * @extends BaseAttribute<AttributeType, array>
 */
abstract class ArrayAttribute extends BaseAttribute {

    /**
     * @param array $value
     * @return AttributeType
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
        return json_decode($value, true);
    }
}