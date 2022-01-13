<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;

/**
 * @extends BaseAttribute<bool>
 */
class BooleanAttribute extends BaseAttribute {

    /** @var array{true, string} */
    public const TRUE_VALUES = [true, "1", "y", "yes", "allow", "true"];
    /** @var array{false, string} */
    public const FALSE_VALUES = [false, "0", "no", "deny", "disallow", "false"];

    /**
     * @param bool $value
     * @return BooleanAttribute
     */
    public function merge(mixed $value) : BooleanAttribute {
        return $this->newInstance($value);
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
        if (in_array($value, self::TRUE_VALUES, true)) {
            return true;
        }
        if (in_array($value, self::FALSE_VALUES, true)) {
            return false;
        }
        throw new AttributeParseException($this, $value);
    }
}