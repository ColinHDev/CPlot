<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @extends BaseAttribute<bool>
 */
abstract class BooleanAttribute extends BaseAttribute {

    /** @var array{"1": true, "y": true, "yes": true, "allow": true, "true": true} */
    private const TRUE_VALUES = ["1" => true, "y" => true, "yes" => true, "allow" => true, "true" => true];
    /** @var array{"0": true, "no": true, "deny": true, "disallow": true, "false": true} */
    private const FALSE_VALUES = ["0" => true, "no" => true, "deny" => true, "disallow" => true, "false" => true];

    public function equals(object $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value === $other->getValue();
    }

    /**
     * @param bool $value
     */
    public function contains(mixed $value) : bool {
        return $this->equals($this->createInstance($value));
    }

    /**
     * @param bool $value
     */
    public function merge(mixed $value) : self {
        return $this->createInstance($value);
    }

    public function getExample() : string {
        return "true";
    }

    public function toString() : string {
        return $this->value ? "true" : "false";
    }

    public function toReadableString() : string {
        return $this->value ? "true" : "false";
    }

    /**
     * @throws AttributeParseException
     */
    public function parse(string $value) : bool {
        $value = strtolower($value);
        if (isset(self::TRUE_VALUES[$value])) {
            return true;
        }
        if (isset(self::FALSE_VALUES[$value])) {
            return false;
        }
        throw new AttributeParseException($this, $value);
    }
}