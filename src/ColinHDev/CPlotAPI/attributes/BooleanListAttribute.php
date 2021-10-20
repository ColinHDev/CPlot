<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;

/**
 * @template AttributeType of BooleanListAttribute
 * @extends BaseAttribute<AttributeType, array<int, bool>>
 */
abstract class BooleanListAttribute extends ArrayAttribute {

    /**
     * @param array<int, bool> | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $values = [];
        foreach ($value as $boolean) {
            $values[] = $boolean ? "true" : "false";
        }
        return json_encode($values);
    }

    /**
     * @return array<int, bool>
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {
        $value = strtolower($value);
        if (array_search($value, BooleanAttribute::TRUE_VALUES, true) !== false) {
            return [true];
        }
        if (array_search($value, BooleanAttribute::FALSE_VALUES, true) !== false) {
            return [false];
        }
        $values = [];
        foreach (json_decode($value, true) as $boolean) {
            if (array_search($boolean, BooleanAttribute::TRUE_VALUES, true) !== false) {
                $values[] = true;
            } else if (array_search($boolean, BooleanAttribute::FALSE_VALUES, true) !== false) {
                $values[] = false;
            } else {
                throw new AttributeParseException($this, $boolean);
            }
        }
        return $values;
    }
}