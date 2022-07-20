<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template TValue of array
 * @phpstan-extends BaseAttribute<TValue>
 */
abstract class ArrayAttribute extends BaseAttribute {

    /**
     * @phpstan-return self<TValue>
     */
    public function merge(mixed $value) : self {
        $values = $this->value;
        foreach ($value as $newValue) {
            /** @phpstan-var TValue $newValueArray */
            $newValueArray = [$newValue];
            $newValueString = $this->toString($newValueArray);
            /** @phpstan-var TValue $values */
            foreach ($values as $oldValue) {
                /** @phpstan-var TValue $oldValueArray */
                $oldValueArray = [$oldValue];
                if ($this->toString($oldValueArray) === $newValueString) {
                    continue 2;
                }
            }
            $values[] = $newValue;
        }
        return $this->createInstance($values);
    }

    /**
     * @phpstan-param TValue | null $value
     * @throws \JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws AttributeParseException
     * @phpstan-return TValue
     */
    public function parse(string $value) : array {
        try {
            $parsed = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($parsed)) {
                return $parsed;
            }
        } catch (\JsonException) {
        }
        throw new AttributeParseException($this, $value);
    }
}