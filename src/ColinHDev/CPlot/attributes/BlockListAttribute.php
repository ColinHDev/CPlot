<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\block\Block;

/**
 * @extends ArrayAttribute<Block[]>
 */
abstract class BlockListAttribute extends ArrayAttribute {

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