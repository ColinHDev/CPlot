<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;

/**
 * @template AttributeType of BooleanAttribute
 * @extends BaseAttribute<AttributeType, bool>
 */
abstract class BooleanAttribute extends BaseAttribute {

    /** @var array{true, string} */
    public const TRUE_VALUES = [true, "1", "y", "yes", "allow", "true"];
    /** @var array{false, string} */
    public const FALSE_VALUES = [false, "0", "no", "deny", "disallow", "false"];

    /**
     * @param bool $value
     * @return AttributeType
     */
    public function merge(mixed $value) : BooleanAttribute {
        return new static($value);
    }

    /**
     * @param bool | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return $value ? "true" : "false";
    }

    /**
     * @throws AttributeParseException
     */
    public function parse(string $value) : bool {
        $value = strtolower($value);
        if (array_search($value, self::TRUE_VALUES, true) !== false) {
            return true;
        }
        if (array_search($value, self::FALSE_VALUES, true) !== false) {
            return false;
        }
        throw new AttributeParseException($this, $value);
    }
}