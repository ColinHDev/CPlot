<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use function count;

/**
 * @phpstan-extends ArrayAttribute<bool[]>
 */
abstract class BooleanListAttribute extends ArrayAttribute {

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        $otherValue = $other->getValue();
        if (count($this->value) !== count($otherValue)) {
            return false;
        }
        /** @phpstan-var bool $bool */
        foreach ($this->value as $i => $bool) {
            if (!isset($otherValue[$i])) {
                return false;
            }
            $otherBool = $otherValue[$i];
            if ($bool !== $otherBool) {
                return false;
            }
        }
        return true;
    }

    public function contains(mixed $value) : bool {
        foreach ($this->value as $currentValue) {
            if ($currentValue === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param bool[] | null $value
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
     * @return bool[]
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
            $array = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            assert(is_array($array));
            /** @phpstan-var array<string|bool> $array */
            foreach ($array as $boolean) {
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