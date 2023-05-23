<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\utils\ParseUtils;
use JsonException;
use pocketmine\block\Block;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function str_contains;
use const JSON_THROW_ON_ERROR;

/**
 * @extends ListAttribute<Block[]>
 */
abstract class BlockListAttribute extends ListAttribute {

    public function equals(object $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        /** @var Block[] $otherValue */
        $otherValue = $other->getValue();
        if (count($this->value) !== count($otherValue)) {
            return false;
        }
        /** @var Block $block */
        foreach ($this->value as $i => $block) {
            if (!isset($otherValue[$i])) {
                return false;
            }
            $otherBlock = $otherValue[$i];
            if (!$block->hasSameTypeId($otherBlock)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Block $value
     */
    public function contains(mixed $value) : bool {
        /** @var Block $currentValue */
        foreach ($this->value as $currentValue) {
            if ($currentValue->hasSameTypeId($value)) {
                return true;
            }
        }
        return false;
    }

    public function getExample() : string {
        return "grass, dirt, stone";
    }

    /**
     * @throws JsonException
     */
    public function toString() : string {
        $blocks = [];
        foreach ($this->value as $block) {
            $blocks[] = ParseUtils::parseStringFromBlock($block);
        }
        return json_encode($blocks, JSON_THROW_ON_ERROR);
    }

    public function toReadableString() : string {
        return implode(", ",
            array_map(
                static function(Block $block) : string {
                    return $block->getName();
                },
                $this->value
            )
        );
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
        try {
            $blocks = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($blocks)) {
                $parsedBlocks = [];
                foreach ($blocks as $blockIdentifier) {
                    if (!is_string($blockIdentifier)) {
                        throw new AttributeParseException($this, $value);
                    }
                    $blockIdentifier = ParseUtils::parseBlockFromString($blockIdentifier);
                    if ($blockIdentifier instanceof Block) {
                        $parsedBlocks[] = $blockIdentifier;
                    }
                }
                return $parsedBlocks;
            }
        } catch(JsonException) {
        }
        if (str_contains($value, ",")) {
            if (str_contains($value, ", ")) {
                $blocks = explode(", ", $value);
            } else {
                $blocks = explode(",", $value);
            }
            $parsedBlocks = [];
            foreach ($blocks as $blockIdentifier) {
                $blockIdentifier = ParseUtils::parseBlockFromString($blockIdentifier);
                if ($blockIdentifier instanceof Block) {
                    $parsedBlocks[] = $blockIdentifier;
                }
            }
            if (count($parsedBlocks) > 0) {
                return $parsedBlocks;
            }
        }
        throw new AttributeParseException($this, $value);
    }
}