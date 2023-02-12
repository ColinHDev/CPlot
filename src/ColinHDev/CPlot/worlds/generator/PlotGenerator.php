<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\generator;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;
use pocketmine\world\World;

class PlotGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_plot";

    private string $worldName;

    private int $biomeID;

    private ?Schematic $roadSchematic = null;
    private ?Schematic $plotSchematic = null;

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

        /** @phpstan-var array{worldName: string, biomeID?: int, roadSchematic?: string, plotSchematic?: string, roadSize?: int, plotSize?: int, groundSize?: int, roadBlock?: string, borderBlock?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $generatorOptions */
        $worldName = ParseUtils::parseStringFromArray($generatorOptions, "worldName");
        assert(is_string($worldName));
        $this->worldName = $worldName;

        $this->biomeID = ParseUtils::parseIntegerFromArray($generatorOptions, "biomeID") ?? BiomeIds::PLAINS;

        $roadSchematicName = ParseUtils::parseStringFromArray($generatorOptions, "roadSchematic") ?? "default";
        if ($roadSchematicName !== "default") {
            $this->roadSchematic = new Schematic("plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $roadSchematicName . "." . Schematic::FILE_EXTENSION);
            $this->roadSchematic->loadFromFile();
        }
        $plotSchematicName = ParseUtils::parseStringFromArray($generatorOptions, "plotSchematic") ?? "default";
        if ($plotSchematicName !== "default") {
            $this->plotSchematic = new Schematic("plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $plotSchematicName . "." . Schematic::FILE_EXTENSION);
            $this->plotSchematic->loadFromFile();
        }

        $this->roadSize = ParseUtils::parseIntegerFromArray($generatorOptions, "roadSize") ?? 7;
        $this->plotSize = ParseUtils::parseIntegerFromArray($generatorOptions, "plotSize") ?? 32;
        $this->groundSize = ParseUtils::parseIntegerFromArray($generatorOptions, "groundSize") ?? 64;

        $roadBlock = ParseUtils::parseBlockFromArray($generatorOptions, "roadBlock") ?? VanillaBlocks::OAK_PLANKS();
        $this->roadBlockFullID = $roadBlock->getStateId();
        $borderBlock = ParseUtils::parseBlockFromArray($generatorOptions, "borderBlock") ?? VanillaBlocks::STONE_SLAB();
        $this->borderBlockFullID = $borderBlock->getStateId();
        $plotFloorBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotFloorBlock") ?? VanillaBlocks::GRASS();
        $this->plotFloorBlockFullID = $plotFloorBlock->getStateId();
        $plotFillBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotFillBlock") ?? VanillaBlocks::DIRT();
        $this->plotFillBlockFullID = $plotFillBlock->getStateId();
        $plotBottomBlock = ParseUtils::parseBlockFromArray($generatorOptions, "plotBottomBlock") ?? VanillaBlocks::BEDROCK();
        $this->plotBottomBlockFullID = $plotBottomBlock->getStateId();
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (!($chunk instanceof Chunk)) {
            return;
        }

        /** @phpstan-var array<TreeRoot> $tiles */
        $tiles = [];
        for ($X = 0; $X < 16; $X++) {
            $x = CoordinateUtils::getRasterCoordinate($chunkX * 16 + $X, $this->roadSize + $this->plotSize);
            $xPlot = $x - $this->roadSize;

            for ($Z = 0; $Z < 16; $Z++) {
                $z = CoordinateUtils::getRasterCoordinate($chunkZ * 16 + $Z, $this->roadSize + $this->plotSize);
                $zPlot = $z - $this->roadSize;

                if ($x < $this->roadSize || $z < $this->roadSize) {
                    if ($this->roadSchematic !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setBiomeId($X, $y, $Z, $this->roadSchematic->getBiomeID($x, $y, $z));
                            $chunk->setBlockStateId($X, $y, $Z, $this->roadSchematic->getBlockStateID($x, $y, $z));
                            $tileNBT = $this->roadSchematic->getTileCompoundTag($x, $y, $z);
                            if ($tileNBT instanceof CompoundTag) {
                                $tileNBT->setInt(Tile::TAG_X, $chunkX * 16 + $X);
                                $tileNBT->setInt(Tile::TAG_Y, $y);
                                $tileNBT->setInt(Tile::TAG_Z, $chunkZ * 16 + $Z);
                                $tiles[] = new TreeRoot($tileNBT, (string) World::blockHash($chunkX * 16 + $X, $y, $chunkZ * 16 + $Z));
                            }
                        }
                    } else {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setBiomeId($X, $y, $Z, $this->biomeID);
                            if ($y > $this->groundSize + 1) {
                                continue;
                            }
                            if ($y === $world->getMinY()) {
                                $chunk->setBlockStateId($X, $y, $Z, $this->plotBottomBlockFullID);
                            } else if ($y === ($this->groundSize + 1)) {
                                if (CoordinateUtils::isRasterPositionOnBorder($x, $z, $this->roadSize)) {
                                    $chunk->setBlockStateId($X, $y, $Z, $this->borderBlockFullID);
                                }
                            } else {
                                $chunk->setBlockStateId($X, $y, $Z, $this->roadBlockFullID);
                            }
                        }
                    }
                } else {
                    if ($this->plotSchematic !== null) {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setBiomeId($X, $y, $Z, $this->plotSchematic->getBiomeID($xPlot, $y, $zPlot));
                            $chunk->setBlockStateId($X, $y, $Z, $this->plotSchematic->getBlockStateID($xPlot, $y, $zPlot));
                            $tileNBT = $this->plotSchematic->getTileCompoundTag($xPlot, $y, $zPlot);
                            if ($tileNBT instanceof CompoundTag) {
                                $tileNBT->setInt(Tile::TAG_X, $chunkX * 16 + $X);
                                $tileNBT->setInt(Tile::TAG_Y, $y);
                                $tileNBT->setInt(Tile::TAG_Z, $chunkZ * 16 + $Z);
                                $tiles[] = new TreeRoot($tileNBT, (string) World::blockHash($chunkX * 16 + $X, $y, $chunkZ * 16 + $Z));
                            }
                        }
                    } else {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $chunk->setBiomeId($X, $y, $Z, $this->biomeID);
                            if ($y > $this->groundSize) {
                                continue;
                            }
                            if ($y === $world->getMinY()) {
                                $chunk->setBlockStateId($X, $y, $Z, $this->plotBottomBlockFullID);
                            } else if ($y === $this->groundSize) {
                                $chunk->setBlockStateId($X, $y, $Z, $this->plotFloorBlockFullID);
                            } else {
                                $chunk->setBlockStateId($X, $y, $Z, $this->plotFillBlockFullID);
                            }
                        }
                    }
                }
            }
        }
        if (count($tiles) > 0) {
            file_put_contents(
                "worlds" . DIRECTORY_SEPARATOR . $this->worldName . DIRECTORY_SEPARATOR . World::chunkHash($chunkX, $chunkZ) . ".cplot_tile_entities",
                zlib_encode((new BigEndianNbtSerializer())->writeMultiple($tiles), ZLIB_ENCODING_GZIP)
            );
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}