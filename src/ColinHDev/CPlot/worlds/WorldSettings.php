<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;

class WorldSettings {

    public const TYPE_CPLOT_DEFAULT = "cplot_default";
    public const TYPE_MYPLOT = "myplot";

    private string $worldType;
    private int $biomeID;

    private string $roadSchematic;
    private string $mergeRoadSchematic;
    private string $plotSchematic;

    private int $roadSize;
    private int $plotSize;
    private int $groundSize;
    private int $coordinateOffset;

    private Block $airBlock;
    private Block $roadBlock;
    private Block $borderBlock;
    private Block $plotFloorBlock;
    private Block $plotFillBlock;
    private Block $plotBottomBlock;

    public function __construct(string $worldType, int $biomeID, string $roadSchematic, string $mergeRoadSchematic, string $plotSchematic, int $roadSize, int $plotSize, int $groundSize, int $coordinateOffset, Block $airBlock, Block $roadBlock, Block $borderBlock, Block $plotFloorBlock, Block $plotFillBlock, Block $plotBottomBlock) {
        $this->worldType = $worldType;
        $this->biomeID = $biomeID;

        $this->roadSchematic = $roadSchematic;
        $this->mergeRoadSchematic = $mergeRoadSchematic;
        $this->plotSchematic = $plotSchematic;

        $this->roadSize = $roadSize;
        $this->plotSize = $plotSize;
        $this->groundSize = $groundSize;
        $this->coordinateOffset = $coordinateOffset;

        $this->airBlock = $airBlock;
        $this->roadBlock = $roadBlock;
        $this->borderBlock = $borderBlock;
        $this->plotFloorBlock = $plotFloorBlock;
        $this->plotFillBlock = $plotFillBlock;
        $this->plotBottomBlock = $plotBottomBlock;
    }

    public function getWorldType() : string {
        return $this->worldType;
    }

    public function getBiomeID() : int {
        return $this->biomeID;
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

	public function getCoordinateOffset() : int {
		return $this->coordinateOffset;
	}

    public function getAirBlock() : Block {
        return $this->airBlock;
    }
    
    public function getRoadBlock() : Block {
        return $this->roadBlock;
    }

    public function getBorderBlock() : Block {
        return $this->borderBlock;
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

    /**
     * @phpstan-return array{worldType: string, biomeID: int, roadSchematic: string, mergeRoadSchematic: string, plotSchematic: string, roadSize: int, plotSize: int, groundSize: int, airBlock: string, roadBlock: string, borderBlock: string, plotFloorBlock: string, plotFillBlock: string, plotBottomBlock: string}
     */
    public function toArray() : array {
        return [
            "worldType" => $this->worldType,
            "biomeID" => $this->biomeID,

            "roadSchematic" => $this->roadSchematic,
            "mergeRoadSchematic" => $this->mergeRoadSchematic,
            "plotSchematic" => $this->plotSchematic,

            "roadSize" => $this->roadSize,
            "plotSize" => $this->plotSize,
            "groundSize" => $this->groundSize, 
            "coordinateOffset" => $this->coordinateOffset,

            "airBlock" => ParseUtils::parseStringFromBlock($this->airBlock),
            "roadBlock" => ParseUtils::parseStringFromBlock($this->roadBlock),
            "borderBlock" => ParseUtils::parseStringFromBlock($this->borderBlock),
            "plotFloorBlock" => ParseUtils::parseStringFromBlock($this->plotFloorBlock),
            "plotFillBlock" => ParseUtils::parseStringFromBlock($this->plotFillBlock),
            "plotBottomBlock" => ParseUtils::parseStringFromBlock($this->plotBottomBlock)
        ];
    }

    public static function fromConfig() : self {
        /** @phpstan-var array{worldType?: string, roadSchematic?: string, biome?: string, mergeRoadSchematic?: string, plotSchematic?: string, roadSize?: int, plotSize?: int, groundSize?: int, airBlock?: string, roadBlock?: string, borderBlock?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $settings */
        $settings = ResourceManager::getInstance()->getConfig()->get("worldSettings", []);
        $biomeName = strtoupper($settings["biome"] ?? "PLAINS");
        unset($settings["biome"]);
        if (defined(BiomeIds::class . "::" . $biomeName) && is_int(constant(BiomeIds::class . "::" . $biomeName))) {
            $settings["biomeID"] = constant(BiomeIds::class . "::" . $biomeName);
        } else {
            $settings["biomeID"] = BiomeIds::PLAINS;
        }
        return self::fromArray($settings);
    }

    /**
     * @phpstan-param array{worldType?: string, biomeID?: int, roadSchematic?: string, mergeRoadSchematic?: string, plotSchematic?: string, roadSize?: int, plotSize?: int, groundSize?: int, airBlock?: string, roadBlock?: string, borderBlock?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $settings
     */
    public static function fromArray(array $settings) : self {
        $worldType = ParseUtils::parseStringFromArray($settings, "worldType") ?? self::TYPE_CPLOT_DEFAULT;
        $biomeID = ParseUtils::parseIntegerFromArray($settings, "biomeID") ?? BiomeIds::PLAINS;

        $roadSchematic = ParseUtils::parseStringFromArray($settings, "roadSchematic") ?? "default";
        $mergeRoadSchematic = ParseUtils::parseStringFromArray($settings, "mergeRoadSchematic") ?? "default";
        $plotSchematic = ParseUtils::parseStringFromArray($settings, "plotSchematic") ?? "default";

        $roadSize = ParseUtils::parseIntegerFromArray($settings, "roadSize") ?? 7;
        $plotSize = ParseUtils::parseIntegerFromArray($settings, "plotSize") ?? 32;
        $groundSize = ParseUtils::parseIntegerFromArray($settings, "groundSize") ?? 64;
        $coordinateOffset = ParseUtils::parseIntegerFromArray($settings, "coordinateOffset") ?? ($worldType === self::TYPE_MYPLOT ? -$roadSize : 0);

        $airBlock = ParseUtils::parseBlockFromArray($settings, "airBlock") ?? VanillaBlocks::AIR();
        $roadBlock = ParseUtils::parseBlockFromArray($settings, "roadBlock") ?? VanillaBlocks::OAK_PLANKS();
        $borderBlock = ParseUtils::parseBlockFromArray($settings, "borderBlock") ?? VanillaBlocks::STONE_SLAB();
        $plotFloorBlock = ParseUtils::parseBlockFromArray($settings, "plotFloorBlock") ?? VanillaBlocks::GRASS();
        $plotFillBlock = ParseUtils::parseBlockFromArray($settings, "plotFillBlock") ?? VanillaBlocks::DIRT();
        $plotBottomBlock = ParseUtils::parseBlockFromArray($settings, "plotBottomBlock") ?? VanillaBlocks::BEDROCK();

        return new self(
            $worldType, $biomeID,
            $roadSchematic, $mergeRoadSchematic, $plotSchematic,
            $roadSize, $plotSize, $groundSize, $coordinateOffset,
            $airBlock, $roadBlock, $borderBlock, $plotFloorBlock, $plotFillBlock, $plotBottomBlock
        );
    }
}