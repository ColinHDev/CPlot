<?php

namespace ColinHDev\CPlotAPI\flags;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;

/**
 * @template TFlagType of BlockListFlag
 * @extends BaseFlag<TFlagType, array<int, Block>>
 */
abstract class BlockListFlag extends ArrayFlag {

    /**
     * @param array<int, Block> | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $blocks = [];
        foreach ($value as $block) {
            $blocks[] = [
                "StringID" => $block->getName(),
                "ID" => $block->getId(),
                "Meta" => $block->getMeta()
            ];
        }
        return json_encode($blocks);
    }

    /**
     * @return array<int, Block>
     */
    public function parse(string $value) : array {
        $blocks = [];
        foreach (json_decode($value, true) as $block) {
            $blocks[] = BlockFactory::getInstance()->get($block["ID"], $block["Meta"]);
        }
        return $blocks;
    }
}