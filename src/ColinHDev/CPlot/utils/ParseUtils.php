<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\UnknownBlock;
use pocketmine\data\bedrock\LegacyBlockIdToStringIdMap;
use pocketmine\item\StringToItemParser;

class ParseUtils {

    /**
     * @phpstan-param array<string|int, string|int> $array
     */
    public static function parseIntegerFromArray(array $array, string | int $key) : ?int {
        if (isset($array[$key]) && is_numeric($array[$key])) {
            return (int) $array[$key];
        }
        return null;
    }

    /**
     * @phpstan-param array<string|int, string|int> $array
     */
    public static function parseStringFromArray(array $array, string | int $key) : ?string {
        if (isset($array[$key])) {
            return (string) $array[$key];
        }
        return null;
    }

    public static function parseStringFromBlock(Block $block) : ?string {
        return (LegacyBlockIdToStringIdMap::getInstance()->legacyToString($block->getId()) ?? "minecraft:info_update") . ";" . $block->getId() . ";" . $block->getMeta();
    }

    /**
     * @phpstan-param array<string|int, string|int> $array
     */
    public static function parseBlockFromArray(array $array, string | int $key) : ?Block {
        if (isset($array[$key]) && is_string($array[$key])) {
            return self::parseBlockFromString($array[$key]);
        }
        return null;
    }

    public static function parseBlockFromString(string $blockIdentifier) : ?Block {
        $item = StringToItemParser::getInstance()->parse($blockIdentifier);
        $block = null;
        if ($item !== null) {
            $block = $item->getBlock();
        } else {
            $blockData = explode(";", $blockIdentifier);
            if (count($blockData) === 3) {
                $blockID = self::parseIntegerFromArray($blockData, 1);
                $blockMeta = self::parseIntegerFromArray($blockData, 2);
                if ($blockID !== null && $blockMeta !== null) {
                    $block = BlockFactory::getInstance()->get($blockID, $blockMeta);
                    if ($block instanceof UnknownBlock) {
                        $block = null;
                    }
                }
            }
        }
        return $block;
    }
}