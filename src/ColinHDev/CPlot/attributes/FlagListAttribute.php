<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\plots\flags\Flag;
use JsonException;

/**
 * @phpstan-extends ArrayAttribute<Flag[]>
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
        /** @phpstan-var Flag $flag */
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
     * @param Flag[] | null $value
     * @throws JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $flags = [];
        foreach ($value as $flag) {
            $flags[] = $flag->toString();
        }
        return json_encode($flags, JSON_THROW_ON_ERROR);
    }

    /**
     * @return Flag[]
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {

        $block = ParseUtils::parseBlockFromString($value);
        if ($block !== null) {
            return [$block];
        }
        $blocks = [];
        try {
            $array = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            assert(is_array($array));
            /** @phpstan-var array<string> $array */
            foreach ($array as $val) {
                $val = ParseUtils::parseBlockFromString($val);
                if ($val instanceof Block) {
                    $blocks[] = $val;
                }
            }
        } catch (JsonException) {
            throw new AttributeParseException($this, $value);
        }
        return $blocks;
    }
}