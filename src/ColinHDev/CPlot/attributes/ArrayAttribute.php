<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @template AttributeValue of array
 * @extends BaseAttribute<AttributeValue>
 */
class ArrayAttribute extends BaseAttribute {

    /**
     * @phpstan-param AttributeValue $value
     * @phpstan-return ArrayAttribute<AttributeValue>
     */
    public function merge(mixed $value) : ArrayAttribute {
        $values = $this->value;
        foreach ($value as $newValue) {
            /** @phpstan-var AttributeValue $newValueArray */
            $newValueArray = [$newValue];
            $newValueString = $this->toString($newValueArray);
            /** @phpstan-var AttributeValue $values */
            foreach ($values as $oldValue) {
                /** @phpstan-var AttributeValue $oldValueArray */
                $oldValueArray = [$oldValue];
                if ($this->toString($oldValueArray) === $newValueString) {
                    continue 2;
                }
            }
            $values[] = $newValue;
        }
        return $this->newInstance($values);
    }

    /**
     * @phpstan-param AttributeValue | null $value
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
     * @phpstan-return AttributeValue
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