<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlotAPI\utils\ParseUtils;
use pocketmine\block\Block;

/**
 * @template AttributeType of BlockListAttribute
 * @extends BaseAttribute<AttributeType, array<int, Block>>
 */
abstract class BlockListAttribute extends ArrayAttribute {

    /**
     * @param array<int, Block> | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $blocks = [];
        foreach ($value as $block) {
            $blocks[] = ParseUtils::parseStringFromBlock($block);
        }
        return json_encode($blocks);
    }

    /**
     * @return array<int, Block>
     */
    public function parse(string $value) : array {
        $block = ParseUtils::parseBlockFromString($value);
        if ($block !== null) {
            return [$block];
        }
        $blocks = [];
        foreach (json_decode($value, true) as $block) {
            $blocks[] = ParseUtils::parseBlockFromString($block);
        }
        return $blocks;
    }
}