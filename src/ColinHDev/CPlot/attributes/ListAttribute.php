<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

/**
 * @template TValue of array<mixed>
 * @extends BaseAttribute<TValue>
 */
abstract class ListAttribute extends BaseAttribute {

    /**
     * @param TValue $value
     * @return self<TValue>
     */
    public function merge(mixed $value) : self {
        /** @var TValue $values */
        $values = $this->value;
        foreach ($value as $newValue) {
            if ($this->contains($newValue)) {
                continue;
            }
            $values[] = $newValue;
        }
        return $this->createInstance($values);
    }
}