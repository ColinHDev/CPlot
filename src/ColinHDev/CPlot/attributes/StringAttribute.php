<?php

namespace ColinHDev\CPlot\attributes;

class StringAttribute extends BaseAttribute {

    /**
     * @param string $value
     */
    public function merge(mixed $value) : StringAttribute {
        return $this->newInstance($value);
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