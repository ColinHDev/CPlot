<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

/**
 * @extends BaseAttribute<string>
 */
abstract class StringAttribute extends BaseAttribute {

    public function equals(object $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value === $other->getValue();
    }

    /**
     * @param string $value
     */
    public function contains(mixed $value) : bool {
        return $this->equals($this->createInstance($value));
    }

    /**
     * @param string $value
     */
    public function merge(mixed $value) : self {
        return $this->createInstance($value);
    }

    /**
     * @param string | null $value
     */
    public function toString(mixed $value = null) : string {
        return $value ?? $this->value;
    }

    public function parse(string $value) : string {
        return $value;
    }
}