<?php

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @extends BaseAttribute<array>
 */
class ArrayAttribute extends BaseAttribute {

    /**
     * @param array $value
     * @return ArrayAttribute
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
        return $this->newInstance($values);
    }

    /**
     * @param array | null $value
     * @throws \JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {
        try {
            $parsed = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($parsed)) {
                return $parsed;
            }
        } catch (\JsonException) {
        }
        throw new AttributeParseException($this, $value);
    }
}