<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

/**
 * @phpstan-extends BaseAttribute<string>
 */
abstract class StringAttribute extends BaseAttribute {

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        return $this->value === $other->getValue();
    }

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