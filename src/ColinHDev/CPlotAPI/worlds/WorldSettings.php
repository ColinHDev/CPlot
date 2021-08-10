<?php

namespace ColinHDev\CPlotAPI\worlds;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use ColinHDev\CPlot\ResourceManager;

class WorldSettings {

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

    public function getBlockRoadString() : string {
        return $this->blockRoad->getId() . (($meta = $this->blockRoad->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function getBlockBorder() : Block {
        return $this->blockBorder;
    }

    public function getBlockBorderString() : string {
        return $this->blockBorder->getId() . (($meta = $this->blockBorder->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function getBlockBorderOnClaim() : Block {
        return $this->blockBorderOnClaim;
    }

    public function getBlockBorderOnClaimString() : string {
        return $this->blockBorderOnClaim->getId() . (($meta = $this->blockBorderOnClaim->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function getBlockPlotFloor() : Block {
        return $this->blockPlotFloor;
    }

    public function getBlockPlotFloorString() : string {
        return $this->blockPlotFloor->getId() . (($meta = $this->blockPlotFloor->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function getBlockPlotFill() : Block {
        return $this->blockPlotFill;
    }

    public function getBlockPlotFillString() : string {
        return $this->blockPlotFill->getId() . (($meta = $this->blockPlotFill->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function getBlockPlotBottom() : Block {
        return $this->blockPlotBottom;
    }

    public function getBlockPlotBottomString() : string {
        return $this->blockPlotBottom->getId() . (($meta = $this->blockPlotBottom->getMeta()) === 0 ? "" : ":" . $meta);
    }

    public function toArray() : array {
        return [
            "schematicRoad" => $this->schematicRoad,
            "schematicMergeRoad" => $this->schematicMergeRoad,
            "schematicPlot" => $this->schematicPlot,

            "sizeRoad" => $this->sizeRoad,
            "sizePlot" => $this->sizePlot,
            "sizeGround" => $this->sizeGround,

            "blockRoad" => $this->blockRoad->getId() . (($meta = $this->blockRoad->getMeta()) === 0 ? "" : ":" . $meta),
            "blockBorder" => $this->blockBorder->getId() . (($meta = $this->blockBorder->getMeta()) === 0 ? "" : ":" . $meta),
            "blockBorderOnClaim" => $this->blockBorderOnClaim->getId() . (($meta = $this->blockBorderOnClaim->getMeta()) === 0 ? "" : ":" . $meta),
            "blockPlotFloor" => $this->blockPlotFloor->getId() . (($meta = $this->blockPlotFloor->getMeta()) === 0 ? "" : ":" . $meta),
            "blockPlotFill" => $this->blockPlotFill->getId() . (($meta = $this->blockPlotFill->getMeta()) === 0 ? "" : ":" . $meta),
            "blockPlotBottom" => $this->blockPlotBottom->getId() . (($meta = $this->blockPlotBottom->getMeta()) === 0 ? "" : ":" . $meta)
        ];
    }

    public static function fromConfig() : self {
        $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
        return self::fromArray($settings);
    }

    public static function fromArray(array $settings) : self {
        $schematicRoad = self::parseString($settings, "schematicRoad", "default");
        $schematicMergeRoad = self::parseString($settings, "schematicMergeRoad", "default");
        $schematicPlot = self::parseString($settings, "schematicPlot", "default");

        $sizeRoad = self::parseNumber($settings, "sizeRoad", 7);
        $sizePlot = self::parseNumber($settings, "sizePlot", 32);
        $sizeGround = self::parseNumber($settings, "sizeGround", 64);

        $blockRoad = self::parseBlock($settings, "blockRoad", VanillaBlocks::OAK_PLANKS());
        $blockBorder = self::parseBlock($settings, "blockBorder", VanillaBlocks::STONE_SLAB());
        $blockBorderOnClaim = self::parseBlock($settings, "blockBorderOnClaim", VanillaBlocks::COBBLESTONE_SLAB());
        $blockPlotFloor = self::parseBlock($settings, "blockPlotFloor", VanillaBlocks::GRASS());
        $blockPlotFill = self::parseBlock($settings, "blockPlotFill", VanillaBlocks::DIRT());
        $blockPlotBottom = self::parseBlock($settings, "blockPlotBottom", VanillaBlocks::BEDROCK());

        return new self(
            $schematicRoad, $schematicMergeRoad, $schematicPlot,
            $sizeRoad, $sizePlot, $sizeGround,
            $blockRoad, $blockBorder, $blockBorderOnClaim, $blockPlotFloor, $blockPlotFill, $blockPlotBottom
        );
    }

    public static function parseBlock(array $array, int | string $key, Block $default) : Block {
        if (isset($array[$key])) {
            $id = $array[$key];
            if (is_numeric($id)) {
                $block = BlockFactory::getInstance()->get((int) $id, 0);
            } else {
                $split = explode(":", $id);
                if (count($split) === 2 && is_numeric($split[0]) && is_numeric($split[1])) {
                    $block = BlockFactory::getInstance()->get((int) $split[0], (int) $split[1]);
                } else {
                    $block = $default;
                }
            }
        } else {
            $block = $default;
        }
        return $block;
    }

    public static function parseString(array $array, int | string $key, string $default) : string {
        if (isset($array[$key])) {
            return (string) $array[$key];
        }
        return $default;
    }

    public static function parseNumber(array $array, int | string $key, int $default) : int {
        if (isset($array[$key]) && is_numeric($array[$key])) {
            return (int) $array[$key];
        }
        return $default;
    }
}