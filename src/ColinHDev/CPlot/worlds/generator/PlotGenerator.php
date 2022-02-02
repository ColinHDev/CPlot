<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\generator;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\generator\Generator;

class PlotGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_plot";

    private string $schematicRoadName;
    private ?Schematic $schematicRoad = null;
    private string $schematicPlotName;
    private ?Schematic $schematicPlot = null;

    private int $roadSize;
    private int $plotSize;
    private int $groundSize;

    private int $roadBlockFullID;
    private int $borderBlockFullID;
    private int $plotFloorBlockFullID;
    private int $plotFillBlockFullID;
    private int $plotBottomBlockFullID;

    public function __construct(int $seed, string $preset) {
        parent::__construct($seed, $preset);
        if ($preset !== "") {
            $generatorOptions = json_decode($preset, true);
            if ($generatorOptions === false || is_null($generatorOptions)) {
                $generatorOptions = [];
            }
        } else {
            $generatorOptions = [];
        }

        $this->schematicRoadName = ParseUtils::parseStringFromArray($generatorOptions, "roadSchematic") ?? "default";
        $this->schematicPlotName = ParseUtils::parseStringFromArray($generatorOptions, "plotSchematic") ?? "default";

        $this->roadSize = ParseUtils::parseIntegerFromArray($generatorOptions, "roadSize") ?? 7;
        $this->plotSize = ParseUtils::parseIntegerFromArray($generatorOptions, "plotSize") ?? 32;
        $this->groundSize = ParseUtils::parseIntegerFromArray($generatorOptions, "groundSize") ?? 64;

        $roadBlock = ParseUtils::parseBlockFromArray($generatorOptions, "roadBlock") ?? VanillaBlocks::OAK_PLANKS();
        $this->roadBlockFullID = $roadBlock->getFullId();
        $borderBlock = ParseUtils::parseBlockFromArray($generatorOptions, "borderBlock") ?? VanillaBlocks::STONE_SLAB();
        $this->borderBlockFullID = $borderBlock->getFullId();
        $plotFloorBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotFloorBlock") ?? VanillaBlocks::GRASS();
        $this->plotFloorBlockFullID = $plotFloorBlock->getFullId();
        $plotFillBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotFillBlock") ?? VanillaBlocks::DIRT();
        $this->plotFillBlockFullID = $plotFillBlock->getFullId();
        $plotBottomBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotBottomBlock") ?? VanillaBlocks::BEDROCK();
        $this->plotBottomBlockFullID = $plotBottomBlock->getFullId();
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {

        if ($this->schematicRoadName !== "default" && $this->schematicRoad === null) {
            $this->schematicRoad = new Schematic($this->schematicRoadName, "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $this->schematicRoadName . "." . Schematic::FILE_EXTENSION);
            if (!$this->schematicRoad->loadFromFile()) {
                $this->schematicRoadName = "default";
            }
        }
        if ($this->schematicPlotName !== "default" && $this->schematicPlot === null) {
            $this->schematicPlot = new Schematic($this->schematicPlotName, "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $this->schematicPlotName . "." . Schematic::FILE_EXTENSION);
            if (!$this->schematicPlot->loadFromFile()) {
                $this->schematicPlotName = "default";
            }
        }

        $chunk = $world->getChunk($chunkX, $chunkZ);
        for ($X = 0; $X < 16; $X++) {
            $x = CoordinateUtils::getRasterCoordinate($chunkX * 16 + $X, $this->roadSize + $this->plotSize);
            $xPlot = $x - $this->roadSize;

            for ($Z = 0; $Z < 16; $Z++) {
                $z = CoordinateUtils::getRasterCoordinate($chunkZ * 16 + $Z, $this->roadSize + $this->plotSize);
                $zPlot = $z - $this->roadSize;

                $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);

                if ($x < $this->roadSize || $z < $this->roadSize) {
                    if ($this->schematicRoadName !== "default" && $this->schematicRoad !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setFullBlock($X, $y, $Z, $this->schematicRoad->getFullBlock($x, $y, $z));
                        }
                    } else {
                        for ($y = $world->getMinY(); $y <= $this->groundSize + 1; $y++) {
                            if ($y === $world->getMinY()) {
                                $chunk->setFullBlock($X, $y, $Z, $this->plotBottomBlockFullID);
                            } else if ($y === ($this->groundSize + 1)) {
                                if (CoordinateUtils::isRasterPositionOnBorder($x, $z, $this->roadSize)) {
                                    $chunk->setFullBlock($X, $y, $Z, $this->borderBlockFullID);
                                }
                            } else {
                                $chunk->setFullBlock($X, $y, $Z, $this->roadBlockFullID);
                            }
                        }
                    }
                } else {
                    if ($this->schematicPlotName !== "default" && $this->schematicPlot !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setFullBlock($X, $y, $Z, $this->schematicPlot->getFullBlock($xPlot, $y, $zPlot));
                        }
                    } else {
                        for ($y = $world->getMinY(); $y <= $this->groundSize; $y++) {
                            if ($y === $world->getMinY()) {
                                $chunk->setFullBlock($X, $y, $Z, $this->plotBottomBlockFullID);
                            } else if ($y === $this->groundSize) {
                                $chunk->setFullBlock($X, $y, $Z, $this->plotFloorBlockFullID);
                            } else {
                                $chunk->setFullBlock($X, $y, $Z, $this->plotFillBlockFullID);
                            }
                        }
                    }
                }
            }
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}