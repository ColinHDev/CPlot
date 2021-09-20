<?php

namespace ColinHDev\CPlot\worlds\generators;

use ColinHDev\CPlotAPI\math\CoordinateUtils;
use pocketmine\world\generator\Generator;
use pocketmine\world\ChunkManager;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\block\VanillaBlocks;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\worlds\schematics\Schematic;

class SchematicGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_schematic";

    private string $schematicName;
    private int $schematicType;
    private ?Schematic $schematic = null;

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

        $this->schematicName = WorldSettings::parseStringFromArray($generatorOptions, "schematic", "default");
        $this->schematicType = WorldSettings::parseIntegerFromArray($generatorOptions, "schematicType", Schematic::TYPE_ROAD);

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
            "schematic" => $this->schematicName,
            "schematicType" => $this->schematicType,

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

        if ($this->schematicName !== "default" && $this->schematic === null) {
            $this->schematic = new Schematic($this->schematicName, "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $this->schematicName . "." . Schematic::FILE_EXTENSION);
            if (!$this->schematic->loadFromFile()) {
                $this->schematicName = "default";
            }
        }

        $chunk = $world->getChunk($chunkX, $chunkZ);

        if ($this->schematicName === "default") {
            if ($this->schematicType === Schematic::TYPE_ROAD) {
                for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                    for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                        $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);
                        if ($x < 0 || $x >= $this->sizeRoad + $this->sizePlot) {
                            continue;
                        }
                        if ($z < 0 || $z >= $this->sizeRoad + $this->sizePlot) {
                            continue;
                        }
                        if ($x >= $this->sizeRoad && $z >= $this->sizeRoad) {
                            continue;
                        }
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
                }

            } else if ($this->schematicType === Schematic::TYPE_PLOT) {
                for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                    for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                        $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);
                        if ($x < 0 || $x >= $this->sizePlot) {
                            continue;
                        }
                        if ($z < 0 || $z >= $this->sizePlot) {
                            continue;
                        }
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
        } else if ($this->schematic !== null) {
            for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $chunk->setFullBlock($X, $y, $Z, $this->schematic->getFullBlock($x, $y, $z));
                    }
                }
            }
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}