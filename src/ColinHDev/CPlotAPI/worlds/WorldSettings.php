<?php

namespace ColinHDev\CPlotAPI\worlds;

use ColinHDev\CPlot\provider\cache\Cacheable;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\item\StringToItemParser;

class WorldSettings implements Cacheable {

    private string $schematicRoad;
    private string $schematicMergeRoad;
    private string $schematicPlot;

    private int $sizeRoad;
    private int $sizePlot;
    private int $sizeGround;

    private Block $blockRoad;
    private Block $blockBorder;
    private Block $blockBorderOnClaim;
    private Block $blockPlotFloor;
    private Block $blockPlotFill;
    private Block $blockPlotBottom;

    public function __construct(string $schematicRoad, string $schematicMergeRoad, string $schematicPlot, int $sizeRoad, int $sizePlot, int $sizeGround, Block $blockRoad, Block $blockBorder, Block $blockBorderOnClaim, Block $blockPlotFloor, Block $blockPlotFill, Block $blockPlotBottom) {
        $this->schematicRoad = $schematicRoad;
        $this->schematicMergeRoad = $schematicMergeRoad;
        $this->schematicPlot = $schematicPlot;

        $this->sizeRoad = $sizeRoad;
        $this->sizePlot = $sizePlot;
        $this->sizeGround = $sizeGround;

        $this->blockRoad = $blockRoad;
        $this->blockBorder = $blockBorder;
        $this->blockBorderOnClaim = $blockBorderOnClaim;
        $this->blockPlotFloor = $blockPlotFloor;
        $this->blockPlotFill = $blockPlotFill;
        $this->blockPlotBottom = $blockPlotBottom;
    }

    public function getSchematicRoad() : string {
        return $this->schematicRoad;
    }

    public function getSchematicMergeRoad() : string {
        return $this->schematicMergeRoad;
    }

    public function getSchematicPlot() : string {
        return $this->schematicPlot;
    }

    public function getSizeRoad() : int {
        return $this->sizeRoad;
    }

    public function getSizePlot() : int {
        return $this->sizePlot;
    }

    public function getSizeGround() : int {
        return $this->sizeGround;
    }

    public function getBlockRoad() : Block {
        return $this->blockRoad;
    }

    public function getBlockBorder() : Block {
        return $this->blockBorder;
    }

    public function getBlockBorderOnClaim() : Block {
        return $this->blockBorderOnClaim;
    }

    public function getBlockPlotFloor() : Block {
        return $this->blockPlotFloor;
    }

    public function getBlockPlotFill() : Block {
        return $this->blockPlotFill;
    }

    public function getBlockPlotBottom() : Block {
        return $this->blockPlotBottom;
    }

    public function toArray() : array {
        return [
            "schematicRoad" => $this->schematicRoad,
            "schematicMergeRoad" => $this->schematicMergeRoad,
            "schematicPlot" => $this->schematicPlot,

            "sizeRoad" => $this->sizeRoad,
            "sizePlot" => $this->sizePlot,
            "sizeGround" => $this->sizeGround,

            "blockRoad" => $this->blockRoad->getFullId(),
            "blockBorder" => $this->blockBorder->getFullId(),
            "blockBorderOnClaim" => $this->blockBorderOnClaim->getFullId(),
            "blockPlotFloor" => $this->blockPlotFloor->getFullId(),
            "blockPlotFill" => $this->blockPlotFill->getFullId(),
            "blockPlotBottom" => $this->blockPlotBottom->getFullId()
        ];
    }

    public static function fromConfig() : self {
        $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
        return self::fromArray($settings);
    }

    public static function fromArray(array $settings) : self {
        $schematicRoad = self::parseStringFromArray($settings, "schematicRoad", "default");
        $schematicMergeRoad = self::parseStringFromArray($settings, "schematicMergeRoad", "default");
        $schematicPlot = self::parseStringFromArray($settings, "schematicPlot", "default");

        $sizeRoad = self::parseIntegerFromArray($settings, "sizeRoad", 7);
        $sizePlot = self::parseIntegerFromArray($settings, "sizePlot", 32);
        $sizeGround = self::parseIntegerFromArray($settings, "sizeGround", 64);

        $blockRoad = self::parseBlockFromArray($settings, "blockRoad", VanillaBlocks::OAK_PLANKS());
        $blockBorder = self::parseBlockFromArray($settings, "blockBorder", VanillaBlocks::STONE_SLAB());
        $blockBorderOnClaim = self::parseBlockFromArray($settings, "blockBorderOnClaim", VanillaBlocks::COBBLESTONE_SLAB());
        $blockPlotFloor = self::parseBlockFromArray($settings, "blockPlotFloor", VanillaBlocks::GRASS());
        $blockPlotFill = self::parseBlockFromArray($settings, "blockPlotFill", VanillaBlocks::DIRT());
        $blockPlotBottom = self::parseBlockFromArray($settings, "blockPlotBottom", VanillaBlocks::BEDROCK());

        return new self(
            $schematicRoad, $schematicMergeRoad, $schematicPlot,
            $sizeRoad, $sizePlot, $sizeGround,
            $blockRoad, $blockBorder, $blockBorderOnClaim, $blockPlotFloor, $blockPlotFill, $blockPlotBottom
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