<?php

namespace ColinHDev\CPlotAPI\worlds;

use ColinHDev\CPlot\provider\cache\Cacheable;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\item\StringToItemParser;

class WorldSettings implements Cacheable {

    private string $roadSchematic;
    private string $mergeRoadSchematic;
    private string $plotSchematic;

    private int $roadSize;
    private int $plotSize;
    private int $groundSize;

    private Block $roadBlock;
    private Block $borderBlock;
    private Block $borderBlockOnClaim;
    private Block $plotFloorBlock;
    private Block $plotFillBlock;
    private Block $plotBottomBlock;

    public function __construct(string $roadSchematic, string $mergeRoadSchematic, string $plotSchematic, int $roadSize, int $plotSize, int $groundSize, Block $roadBlock, Block $borderBlock, Block $borderBlockOnClaim, Block $plotFloorBlock, Block $plotFillBlock, Block $plotBottomBlock) {
        $this->roadSchematic = $roadSchematic;
        $this->mergeRoadSchematic = $mergeRoadSchematic;
        $this->plotSchematic = $plotSchematic;

        $this->roadSize = $roadSize;
        $this->plotSize = $plotSize;
        $this->groundSize = $groundSize;

        $this->roadBlock = $roadBlock;
        $this->borderBlock = $borderBlock;
        $this->borderBlockOnClaim = $borderBlockOnClaim;
        $this->plotFloorBlock = $plotFloorBlock;
        $this->plotFillBlock = $plotFillBlock;
        $this->plotBottomBlock = $plotBottomBlock;
    }

    public function getRoadSchematic() : string {
        return $this->roadSchematic;
    }

    public function getMergeRoadSchematic() : string {
        return $this->mergeRoadSchematic;
    }

    public function getPlotSchematic() : string {
        return $this->plotSchematic;
    }

    public function getRoadSize() : int {
        return $this->roadSize;
    }

    public function getPlotSize() : int {
        return $this->plotSize;
    }

    public function getGroundSize() : int {
        return $this->groundSize;
    }

    public function getRoadBlock() : Block {
        return $this->roadBlock;
    }

    public function getBorderBlock() : Block {
        return $this->borderBlock;
    }

    public function getBorderBlockOnClaim() : Block {
        return $this->borderBlockOnClaim;
    }

    public function getPlotFloorBlock() : Block {
        return $this->plotFloorBlock;
    }

    public function getPlotFillBlock() : Block {
        return $this->plotFillBlock;
    }

    public function getPlotBottomBlock() : Block {
        return $this->plotBottomBlock;
    }

    public function toArray() : array {
        return [
            "roadSchematic" => $this->roadSchematic,
            "mergeRoadSchematic" => $this->mergeRoadSchematic,
            "plotSchematic" => $this->plotSchematic,

            "roadSize" => $this->roadSize,
            "plotSize" => $this->plotSize,
            "groundSize" => $this->groundSize,

            "roadBlock" => $this->roadBlock->getFullId(),
            "borderBlock" => $this->borderBlock->getFullId(),
            "borderBlockOnClaim" => $this->borderBlockOnClaim->getFullId(),
            "plotFloorBlock" => $this->plotFloorBlock->getFullId(),
            "plotFillBlock" => $this->plotFillBlock->getFullId(),
            "plotBottomBlock" => $this->plotBottomBlock->getFullId()
        ];
    }

    public static function fromConfig() : self {
        $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
        return self::fromArray($settings);
    }

    public static function fromArray(array $settings) : self {
        $roadSchematic = self::parseStringFromArray($settings, "roadSchematic", "default");
        $mergeRoadSchematic = self::parseStringFromArray($settings, "mergeRoadSchematic", "default");
        $plotSchematic = self::parseStringFromArray($settings, "plotSchematic", "default");

        $roadSize = self::parseIntegerFromArray($settings, "roadSize", 7);
        $plotSize = self::parseIntegerFromArray($settings, "plotSize", 32);
        $groundSize = self::parseIntegerFromArray($settings, "groundSize", 64);

        $roadBlock = self::parseBlockFromArray($settings, "roadBlock", VanillaBlocks::OAK_PLANKS());
        $borderBlock = self::parseBlockFromArray($settings, "borderBlock", VanillaBlocks::STONE_SLAB());
        $borderBlockOnClaim = self::parseBlockFromArray($settings, "borderBlockOnClaim", VanillaBlocks::COBBLESTONE_SLAB());
        $plotFloorBlock = self::parseBlockFromArray($settings, "plotFloorBlock", VanillaBlocks::GRASS());
        $plotFillBlock = self::parseBlockFromArray($settings, "plotFillBlock", VanillaBlocks::DIRT());
        $plotBottomBlock = self::parseBlockFromArray($settings, "plotBottomBlock", VanillaBlocks::BEDROCK());

        return new self(
            $roadSchematic, $mergeRoadSchematic, $plotSchematic,
            $roadSize, $plotSize, $groundSize,
            $roadBlock, $borderBlock, $borderBlockOnClaim, $plotFloorBlock, $plotFillBlock, $plotBottomBlock
        );
    }

    // TODO: The following methods are not only used for parsing values exclusively related to world settings
    //  but are used throughout the entire plugin and could therefore be moved to their own class
    public static function parseBlockFromArray(array $array, string $key, ?Block $default = null) : ?Block {
        if (isset($array[$key])) {
            $block = self::parseBlock($array[$key], $default);
        } else {
            $block = $default;
        }
        return $block;
    }

    public static function parseBlock(string $blockIdentifier, ?Block $default = null) : ?Block {
        $item = StringToItemParser::getInstance()->parse($blockIdentifier);
        if ($item !== null) {
            $block = $item->getBlock();
        } else {
            $block = $default;
        }
        return $block;
    }

    public static function parseStringFromArray(array $array, string $key, ?string $default = null) : ?string {
        if (isset($array[$key])) {
            return (string) $array[$key];
        }
        return $default;
    }

    public static function parseIntegerFromArray(array $array, string $key, ?int $default = null) : ?int {
        if (isset($array[$key]) && is_numeric($array[$key])) {
            return (int) $array[$key];
        }
        return $default;
    }
}