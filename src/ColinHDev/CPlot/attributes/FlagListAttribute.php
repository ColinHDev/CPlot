<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\plots\flags\Flag;
use JsonException;

/**
 * @phpstan-extends ArrayAttribute<array<BaseAttribute<mixed>&Flag<mixed>>>
 */
abstract class FlagListAttribute extends ArrayAttribute {

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        $otherValue = $other->getValue();
        if (count($this->value) !== count($otherValue)) {
            return false;
        }
        /** @phpstan-var BaseAttribute<mixed>&Flag<mixed> $flag */
        foreach ($this->value as $i => $flag) {
            if (!isset($otherValue[$i])) {
                return false;
            }
            $otherFlag = $otherValue[$i];
            if (!$flag->equals($otherFlag)) {
                return false;
            }
        }
        return true;
    }

    public function contains(mixed $value) : bool {
        foreach ($this->value as $currentValue) {
            if ($currentValue->equals($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<BaseAttribute&Flag> | null $value
     * @phpstan-param array<BaseAttribute<mixed>&Flag<mixed>> | null $value
     * @throws JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $flags = [];
        foreach ($value as $flag) {
            $flags[] = $flag->getID() . "=" . $flag->toString();
        }
        return json_encode($flags, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<BaseAttribute&Flag>
     * @phpstan-return array<BaseAttribute<mixed>&Flag<mixed>>
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {

        $flags = [];
        try {
            $array = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            assert(is_array($array));
        } catch (JsonException) {
            throw new AttributeParseException($this, $value);
        }
        return $flags;
    }
}