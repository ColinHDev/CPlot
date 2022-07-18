<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template TAttributeType of ArrayAttribute
 * @phpstan-template TAttributeValue of array
 * @phpstan-extends BaseAttribute<TAttributeType, TAttributeValue>
 */
abstract class ArrayAttribute extends BaseAttribute {

    public function merge(mixed $value) : BaseAttribute {
        $values = $this->value;
        foreach ($value as $newValue) {
            /** @phpstan-var TAttributeValue $newValueArray */
            $newValueArray = [$newValue];
            $newValueString = $this->toString($newValueArray);
            /** @phpstan-var TAttributeValue $values */
            foreach ($values as $oldValue) {
                /** @phpstan-var TAttributeValue $oldValueArray */
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
     * @phpstan-param TAttributeValue | null $value
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
     * @phpstan-return TAttributeValue
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