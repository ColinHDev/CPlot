<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\block\Block;
use function count;

/**
 * @phpstan-template TAttributeType of BlockListAttribute
 * @phpstan-extends ArrayAttribute<TAttributeType, Block[]>
 */
abstract class BlockListAttribute extends ArrayAttribute {

    public function equals(BaseAttribute $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        if (count($this->value) !== count($other->getValue())) {
            return false;
        }
        /** @phpstan-var Block $block */
        foreach ($this->value as $i => $block) {
            if (!isset($other->getValue()[$i])) {
                return false;
            }
            $otherBlock = $other->getValue()[$i];
            if (!$block->isSameState($otherBlock)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Block[] | null $value
     * @throws \JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $blocks = [];
        foreach ($value as $block) {
            $blocks[] = ParseUtils::parseStringFromBlock($block);
        }
        return json_encode($blocks, JSON_THROW_ON_ERROR);
    }

    /**
     * @return Block[]
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
        } catch (\JsonException) {
            throw new AttributeParseException($this, $value);
        }
        return $blocks;
    }
}