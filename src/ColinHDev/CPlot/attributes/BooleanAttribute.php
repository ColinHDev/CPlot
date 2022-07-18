<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template TAttributeType of BooleanAttribute
 * @phpstan-extends BaseAttribute<TAttributeType, bool>
 */
abstract class BooleanAttribute extends BaseAttribute {

    /** @var array{true, string} */
    public const TRUE_VALUES = [true, "1", "y", "yes", "allow", "true"];
    /** @var array{false, string} */
    public const FALSE_VALUES = [false, "0", "no", "deny", "disallow", "false"];

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value === $other->getValue();
    }

    public function merge(mixed $value) : BaseAttribute {
        return $this->createInstance($value);
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