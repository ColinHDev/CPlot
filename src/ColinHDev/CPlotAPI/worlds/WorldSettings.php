<?php

namespace ColinHDev\CPlotAPI\worlds;

use ColinHDev\CPlot\provider\cache\Cacheable;
use ColinHDev\CPlotAPI\utils\ParseUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use ColinHDev\CPlot\ResourceManager;

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

            "roadBlock" => ParseUtils::parseStringFromBlock($this->roadBlock),
            "borderBlock" => ParseUtils::parseStringFromBlock($this->borderBlock),
            "borderBlockOnClaim" => ParseUtils::parseStringFromBlock($this->borderBlockOnClaim),
            "plotFloorBlock" => ParseUtils::parseStringFromBlock($this->plotFloorBlock),
            "plotFillBlock" => ParseUtils::parseStringFromBlock($this->plotFillBlock),
            "plotBottomBlock" => ParseUtils::parseStringFromBlock($this->plotBottomBlock)
        ];
    }

    public static function fromConfig() : self {
        $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
        return self::fromArray($settings);
    }

    public static function fromArray(array $settings) : self {
        $roadSchematic = ParseUtils::parseStringFromArray($settings, "roadSchematic", "default");
        $mergeRoadSchematic = ParseUtils::parseStringFromArray($settings, "mergeRoadSchematic", "default");
        $plotSchematic = ParseUtils::parseStringFromArray($settings, "plotSchematic", "default");

        $roadSize = ParseUtils::parseIntegerFromArray($settings, "roadSize", 7);
        $plotSize = ParseUtils::parseIntegerFromArray($settings, "plotSize", 32);
        $groundSize = ParseUtils::parseIntegerFromArray($settings, "groundSize", 64);

        $roadBlock = ParseUtils::parseBlockFromArray($settings, "roadBlock", VanillaBlocks::OAK_PLANKS());
        $borderBlock = ParseUtils::parseBlockFromArray($settings, "borderBlock", VanillaBlocks::STONE_SLAB());
        $borderBlockOnClaim = ParseUtils::parseBlockFromArray($settings, "borderBlockOnClaim", VanillaBlocks::COBBLESTONE_SLAB());
        $plotFloorBlock = ParseUtils::parseBlockFromArray($settings, "plotFloorBlock", VanillaBlocks::GRASS());
        $plotFillBlock = ParseUtils::parseBlockFromArray($settings, "plotFillBlock", VanillaBlocks::DIRT());
        $plotBottomBlock = ParseUtils::parseBlockFromArray($settings, "plotBottomBlock", VanillaBlocks::BEDROCK());

        return new self(
            $roadSchematic, $mergeRoadSchematic, $plotSchematic,
            $roadSize, $plotSize, $groundSize,
            $roadBlock, $borderBlock, $borderBlockOnClaim, $plotFloorBlock, $plotFillBlock, $plotBottomBlock
        );
    }
}