<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\generator;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use ColinHDev\CPlot\worlds\schematic\SchematicTypes;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;

class SchematicGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_schematic";

    private string $schematicName;
    private string $schematicType;
    private ?Schematic $schematic = null;

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
        $generatorOptions = [];
        if ($preset !== "") {
            $generatorOptions = json_decode($preset, true);
            if ($generatorOptions === false || is_null($generatorOptions)) {
                $generatorOptions = [];
            }
        }

        /** @phpstan-var array{schematicName?: string, schematicType?: string, roadSize?: int, plotSize?: int, groundSize?: int, roadBlock?: string, borderBlock?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $generatorOptions */
        $this->schematicName = ParseUtils::parseStringFromArray($generatorOptions, "schematicName") ?? "default";
        $this->schematicType = ParseUtils::parseStringFromArray($generatorOptions, "schematicType") ?? SchematicTypes::TYPE_ROAD;

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

        if ($this->schematicName !== "default" && $this->schematic === null) {
            $this->schematic = new Schematic($this->schematicName, "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $this->schematicName . "." . Schematic::FILE_EXTENSION);
            if (!$this->schematic->loadFromFile()) {
                $this->schematicName = "default";
            }
        }

        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (!($chunk instanceof Chunk)) {
            return;
        }

        if ($this->schematicName === "default") {
            if ($this->schematicType === SchematicTypes::TYPE_ROAD) {
                for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                    for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                        $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);
                        if ($x < 0 || $x >= $this->roadSize + $this->plotSize) {
                            continue;
                        }
                        if ($z < 0 || $z >= $this->roadSize + $this->plotSize) {
                            continue;
                        }
                        if ($x >= $this->roadSize && $z >= $this->roadSize) {
                            continue;
                        }
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
                }

            } else if ($this->schematicType === SchematicTypes::TYPE_PLOT) {
                for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                    for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                        $chunk->setBiomeId($X, $Z, BiomeIds::PLAINS);
                        if ($x < 0 || $x >= $this->plotSize) {
                            continue;
                        }
                        if ($z < 0 || $z >= $this->plotSize) {
                            continue;
                        }
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