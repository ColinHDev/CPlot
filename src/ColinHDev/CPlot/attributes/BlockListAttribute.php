<?php

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\block\Block;

/**
 * @extends BaseAttribute<Block[]>
 */
class BlockListAttribute extends ArrayAttribute {

    /**
     * @param array<int, Block> | null $value
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
     * @return array<int, Block>
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {
        $block = ParseUtils::parseBlockFromString($value);
        if ($block !== null) {
            return [$block];
        }
        $blocks = [];
        try {
            foreach (json_decode($value, true, 512, JSON_THROW_ON_ERROR) as $block) {
                $blocks[] = ParseUtils::parseBlockFromString($block);
            }
        } catch (\JsonException) {
            throw new AttributeParseException($this, $value);
        }
        return $blocks;
    }
}