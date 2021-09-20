<?php

namespace ColinHDev\CPlot\worlds\generators;

use ColinHDev\CPlotAPI\math\CoordinateUtils;
use pocketmine\world\generator\Generator;
use pocketmine\world\ChunkManager;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\block\VanillaBlocks;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\worlds\schematics\Schematic;

class PlotGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_plot";

    private string $schematicRoadName;
    private ?Schematic $schematicRoad = null;
    private string $schematicPlotName;
    private ?Schematic $schematicPlot = null;

    private int $sizeRoad;
    private int $sizePlot;
    private int $sizeGround;

    private int $blockRoadId;
    private int $blockBorderId;
    private int $blockPlotFloorId;
    private int $blockPlotFillId;
    private int $blockPlotBottomId;

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

        $this->schematicRoadName = WorldSettings::parseStringFromArray($generatorOptions, "schematicRoad", "default");
        $this->schematicPlotName = WorldSettings::parseStringFromArray($generatorOptions, "schematicPlot", "default");

        $this->sizeRoad = WorldSettings::parseIntegerFromArray($generatorOptions, "sizeRoad", 7);
        $this->sizePlot = WorldSettings::parseIntegerFromArray($generatorOptions, "sizePlot", 32);
        $this->sizeGround = WorldSettings::parseIntegerFromArray($generatorOptions, "sizeGround", 64);

        $blockRoad = WorldSettings::parseBlockFromArray($generatorOptions, "blockRoad", VanillaBlocks::OAK_PLANKS());
        $this->blockRoadId = $blockRoad->getFullId();
        $blockBorder = WorldSettings::parseBlockFromArray($generatorOptions, "blockBorder", VanillaBlocks::STONE_SLAB());
        $this->blockBorderId = $blockBorder->getFullId();
        $blockPlotFloor = WorldSettings::parseBlockFromArray($generatorOptions, "blockPlotFloor", VanillaBlocks::GRASS());
        $this->blockPlotFloorId = $blockPlotFloor->getFullId();
        $blockPlotFill = WorldSettings::parseBlockFromArray($generatorOptions, "blockPlotFill", VanillaBlocks::DIRT());
        $this->blockPlotFillId = $blockPlotFill->getFullId();
        $blockPlotBottom = WorldSettings::parseBlockFromArray($generatorOptions, "blockPlotBottom", VanillaBlocks::BEDROCK());
        $this->blockPlotBottomId = $blockPlotBottom->getFullId();

        $this->preset = (string) json_encode([
            "schematicRoad" => $this->schematicRoadName,
            "schematicPlot" => $this->schematicPlotName,

            "sizeRoad" => $this->sizeRoad,
            "sizePlot" => $this->sizePlot,
            "sizeGround" => $this->sizeGround,

            "blockRoad" => $blockRoad->getName(),
            "blockBorder" => $blockBorder->getName(),
            "blockPlotFloor" => $blockPlotFloor->getName(),
            "blockPlotFill" => $blockPlotFill->getName(),
            "blockPlotBottom" => $blockPlotBottom->getName()
        ]);
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
            $x = CoordinateUtils::getRasterCoordinate($chunkX * 16 + $X, $this->sizeRoad + $this->sizePlot);
            $xPlot = $x - $this->sizeRoad;

            for ($Z = 0; $Z < 16; $Z++) {
                $z = CoordinateUtils::getRasterCoordinate($chunkZ * 16 + $Z, $this->sizeRoad + $this->sizePlot);
                $zPlot = $z - $this->sizeRoad;

                $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);

                if ($x < $this->sizeRoad || $z < $this->sizeRoad) {
                    if ($this->schematicRoadName !== "default" && $this->schematicRoad !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setFullBlock($X, $y, $Z, $this->schematicRoad->getFullBlock($x, $y, $z));
                        }
                    } else {
                        for ($y = $world->getMinY(); $y <= $this->sizeGround + 1; $y++) {
                            if ($y === $world->getMinY()) {
                                $chunk->setFullBlock($X, $y, $Z, $this->blockPlotBottomId);
                            } else if ($y === ($this->sizeGround + 1)) {
                                if (CoordinateUtils::isRasterPositionOnBorder($x, $z, $this->sizeRoad)) {
                                    $chunk->setFullBlock($X, $y, $Z, $this->blockBorderId);
                                }
                            } else {
                                $chunk->setFullBlock($X, $y, $Z, $this->blockRoadId);
                            }
                        }
                    }
                } else {
                    if ($this->schematicPlotName !== "default" && $this->schematicPlot !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setFullBlock($X, $y, $Z, $this->schematicPlot->getFullBlock($xPlot, $y, $zPlot));
                        }
                    } else {
                        for ($y = $world->getMinY(); $y <= $this->sizeGround; $y++) {
                            if ($y === $world->getMinY()) {
                                $chunk->setFullBlock($X, $y, $Z, $this->blockPlotBottomId);
                            } else if ($y === $this->sizeGround) {
                                $chunk->setFullBlock($X, $y, $Z, $this->blockPlotFloorId);
                            } else {
                                $chunk->setFullBlock($X, $y, $Z, $this->blockPlotFillId);
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