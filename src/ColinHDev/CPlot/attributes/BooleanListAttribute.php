<?php

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @extends BaseAttribute<bool[]>
 */
class BooleanListAttribute extends ArrayAttribute {

    /**
     * @param array<int, bool> | null $value
     * @throws \JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $values = [];
        foreach ($value as $boolean) {
            $values[] = $boolean ? "true" : "false";
        }
        return json_encode($values, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, bool>
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {
        $value = strtolower($value);
        if (in_array($value, BooleanAttribute::TRUE_VALUES, true)) {
            return [true];
        }
        if (in_array($value, BooleanAttribute::FALSE_VALUES, true)) {
            return [false];
        }
        $values = [];
        try {
            foreach (json_decode($value, true, 512, JSON_THROW_ON_ERROR) as $boolean) {
                if (in_array($boolean, BooleanAttribute::TRUE_VALUES, true)) {
                    $values[] = true;
                } else if (in_array($boolean, BooleanAttribute::FALSE_VALUES, true)) {
                    $values[] = false;
                } else {
                    throw new AttributeParseException($this, $boolean);
                }
            }
        } catch (\JsonException) {
            throw new AttributeParseException($this, $value);
        }
        return $values;
    }
}