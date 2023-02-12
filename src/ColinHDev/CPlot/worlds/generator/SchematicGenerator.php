<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\generator;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use ColinHDev\CPlot\worlds\schematic\SchematicTypes;
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

class SchematicGenerator extends Generator {

    public const GENERATOR_NAME = "cplot_schematic";

    private string $worldName;

    private int $biomeID;

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

        /** @phpstan-var array{worldName: string, biomeID?: int, schematicName?: string, schematicType?: string, roadSize?: int, plotSize?: int, groundSize?: int, roadBlock?: string, borderBlock?: string, plotFloorBlock?: string, plotFillBlock?: string, plotBottomBlock?: string} $generatorOptions */
        $worldName = ParseUtils::parseStringFromArray($generatorOptions, "worldName");
        assert(is_string($worldName));
        $this->worldName = $worldName;

        $this->biomeID = ParseUtils::parseIntegerFromArray($generatorOptions, "biomeID") ?? BiomeIds::PLAINS;

        $this->schematicType = ParseUtils::parseStringFromArray($generatorOptions, "schematicType") ?? SchematicTypes::TYPE_ROAD;
        $schematicName = ParseUtils::parseStringFromArray($generatorOptions, "schematicName") ?? "default";
        if ($schematicName !== "default" && $this->schematic === null) {
            $this->schematic = new Schematic("plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $schematicName . "." . Schematic::FILE_EXTENSION);
            $this->schematic->loadFromFile();
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

        if ($this->schematic instanceof Schematic) {
            /** @phpstan-var array<TreeRoot> $tiles */
            $tiles = [];
            for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $chunk->setBiomeId($X, $y, $Z, $this->schematic->getBiomeID($X, $y, $Z));
                        $chunk->setBlockStateId($X, $y, $Z, $this->schematic->getBlockStateID($x, $y, $z));
                        $tileNBT = $this->schematic->getTileCompoundTag($x, $y, $z);
                        if ($tileNBT instanceof CompoundTag) {
                            $tileNBT->setInt(Tile::TAG_X, $chunkX * 16 + $X);
                            $tileNBT->setInt(Tile::TAG_Y, $y);
                            $tileNBT->setInt(Tile::TAG_Z, $chunkZ * 16 + $Z);
                            $tiles[] = new TreeRoot($tileNBT, (string) World::blockHash($chunkX * 16 + $X, $y, $chunkZ * 16 + $Z));
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
        } else if ($this->schematicType === SchematicTypes::TYPE_ROAD) {
            for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $chunk->setBiomeId($X, $y, $Z, $this->biomeID);
                        if (
                            $x < 0 || $x >= $this->roadSize + $this->plotSize ||
                            $z < 0 || $z >= $this->roadSize + $this->plotSize ||
                            ($x >= $this->roadSize && $z >= $this->roadSize) ||
                            $y > $this->groundSize + 1
                        ) {
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
            }
        } else if ($this->schematicType === SchematicTypes::TYPE_PLOT) {
            for ($X = 0, $x = $chunkX * 16; $X < 16; $X++, $x++) {
                for ($Z = 0, $z = $chunkZ * 16; $Z < 16; $Z++, $z++) {
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        $chunk->setBiomeId($X, $y, $Z, $this->biomeID);
                        if ($x < 0 || $x >= $this->plotSize ||
                            $z < 0 || $z >= $this->plotSize ||
                            $y > $this->groundSize) {
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

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}