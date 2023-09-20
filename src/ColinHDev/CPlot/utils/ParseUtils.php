<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils;

use pocketmine\block\Block;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use function base64_decode;
use function base64_encode;
use function count;
use function explode;
use function get_class;
use function is_string;
use function preg_match_all;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

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

    /**
     * @throws ParseException
     */
    public static function parseStringFromBlock(Block $block) : string {
        $compressedTreeRoot = zlib_encode(
            (new BigEndianNbtSerializer())->write(new TreeRoot(
                GlobalBlockStateHandlers::getSerializer()->serialize($block->getStateId())->toNbt()
            )),
            ZLIB_ENCODING_GZIP
        );
        if (!is_string($compressedTreeRoot)) {
            throw new ParseException("Block " . get_class($block) . " could not be parsed into a string.");
        }
        // Returning only the GZIP compressed data resulted in errors e.g. when json encoding the string because of
        // malformed UTF-8 characters. Therefore, we base64 encode the compressed data.
        return base64_encode($compressedTreeRoot);
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
        $block = self::parseBlockFromBlockName($blockIdentifier);
        if ($block !== null) {
            return $block;
        }
        $block = self::parseBlockFromCompressedTreeRoot($blockIdentifier);
        if ($block !== null) {
            return $block;
        }
        return self::parseBlockFromIdMetaString($blockIdentifier, ";");
    }

    private static function parseBlockFromBlockName(string $blockName) : ?Block {
        $item = StringToItemParser::getInstance()->parse($blockName);
        return $item?->getBlock();
    }

    private static function parseBlockFromCompressedTreeRoot(string $compressedTreeRoot) : ?Block {
        $decompressed = base64_decode($compressedTreeRoot, true);
        if (!is_string($decompressed)) {
            return null;
        }
        // Apparently zlib_decode() not only returns false but also issues a warning if the data could not be
        // decompressed. (This is not stated in the php wiki.) Since PocketMine-MP automatically converts warnings to
        // exceptions, we silence the warning here.
        $decompressed = @zlib_decode($decompressed);
        if (!is_string($decompressed)) {
            return null;
        }
        try {
            $compoundTag = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
        } catch (NbtDataException) {
            return null;
        }
        try {
            $blockState = GlobalBlockStateHandlers::getUpgrader()->upgradeBlockStateNbt($compoundTag);
        } catch (BlockStateDeserializeException) {
            return null;
        }
        try {
            return GlobalBlockStateHandlers::getDeserializer()->deserializeBlock($blockState);
        } catch (UnsupportedBlockStateException) {
            return null;
        }
    }

    /**
     * @param non-empty-string $separator
     */
    private static function parseBlockFromIdMetaString(string $idMetaString, string $separator) : ?Block {
        $blockData = explode($separator, $idMetaString);
        if (count($blockData) !== 3) {
            return null;
        }
        $blockID = self::parseIntegerFromArray($blockData, 1);
        $blockMeta = self::parseIntegerFromArray($blockData, 2);
        if ($blockID === null || $blockMeta === null) {
            return null;
        }
        try {
            return GlobalBlockStateHandlers::getDeserializer()->deserializeBlock(
                GlobalBlockStateHandlers::getUpgrader()->upgradeIntIdMeta($blockID, $blockMeta)
            );
        } catch (UnsupportedBlockStateException) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    public static function parseAliasesFromString(string $aliases) : array {
        // Only allow letters and numbers to be used in aliases
        preg_match_all('/[^\s,.;]+/', $aliases, $matches);
        return $matches[0];
    }

	/**
	 * This is different from CPlot because of the $blockData separator character and output keys
	 *
	 * @phpstan-param array<string|int, string|int> $array
	 */
	public static function parseMyPlotBlock(array $array, string | int $key) : ?Block {
        $idMetaString = self::parseStringFromArray($array, $key);
        if ($idMetaString !== null) {
            return self::parseBlockFromIdMetaString($idMetaString, ":");
        }
        return null;
	}
}
