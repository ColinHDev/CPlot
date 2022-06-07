<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\worlds\generator;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\utils\ParseUtils;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\UnknownBlock;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;

class MyPlotGenerator extends Generator {

    public const GENERATOR_NAME = "myplot";
    public const MYPLOT_OFFSET = 7;

    private int $biomeID;

    private int $roadSize;
    private int $plotSize;
    private int $groundSize;

    private int $roadBlockFullID;
    private int $borderBlockFullID;
    private int $plotFloorBlockFullID;
    private int $plotFillBlockFullID;
    private int $plotBottomBlockFullID;

    public function __construct(int $seed, string $preset){
        parent::__construct($seed, $preset);
        $generatorOptions = [];
        if ($preset !== "") {
            $generatorOptions = \json_decode($preset, true, flags: \JSON_THROW_ON_ERROR);
            if ($generatorOptions === false || is_null($generatorOptions)) {
                $generatorOptions = [];
            }
        }

        $this->biomeID = BiomeIds::PLAINS;

        $this->roadSize = ParseUtils::parseIntegerFromArray($generatorOptions, "RoadWidth") ?? 7;
        $this->plotSize = ParseUtils::parseIntegerFromArray($generatorOptions, "PlotSize") ?? 32;
        $this->groundSize = ParseUtils::parseIntegerFromArray($generatorOptions, "GroundHeight") ?? 64;

        $roadBlock = $this->parseMyPlotBlock($generatorOptions, "RoadBlock") ?? VanillaBlocks::OAK_PLANKS();
        $this->roadBlockFullID = $roadBlock->getFullId();
        $borderBlock = $this->parseMyPlotBlock($generatorOptions, "WallBlock") ?? VanillaBlocks::STONE_SLAB();
        $this->borderBlockFullID = $borderBlock->getFullId();
        $plotFloorBlock = $this->parseMyPlotBlock($generatorOptions, "PlotFloorBlock") ?? VanillaBlocks::GRASS();
        $this->plotFloorBlockFullID = $plotFloorBlock->getFullId();
        $plotFillBlock = $this->parseMyPlotBlock($generatorOptions, "PlotFillBlock") ?? VanillaBlocks::DIRT();
        $this->plotFillBlockFullID = $plotFillBlock->getFullId();
        $plotBottomBlock = $this->parseMyPlotBlock($generatorOptions, "BottomBlock") ?? VanillaBlocks::BEDROCK();
        $this->plotBottomBlockFullID = $plotBottomBlock->getFullId();
    }

    /**
     * This is different from CPlot because of the $blockData separator character and output keys
     *
     * @phpstan-param array<string|int, string|int> $array
     */
    private function parseMyPlotBlock(array $array, string|int $key) : ?Block {
        if (isset($array[$key]) && is_string($array[$key])) {
            $blockData = explode(":", $array[$key]);
            $blockID = ParseUtils::parseIntegerFromArray($blockData, 0);
            $blockMeta = ParseUtils::parseIntegerFromArray($blockData, 1) ?? 0;
            if ($blockID !== null) {
                $block = BlockFactory::getInstance()->get($blockID, $blockMeta);
                if ($block instanceof UnknownBlock) {
                    $block = null;
                }
                return $block;
            }
        }
        return null;
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (!($chunk instanceof Chunk)) {
            return;
        }

        for ($X = 0; $X < Chunk::EDGE_LENGTH; $X++) {
            $x = CoordinateUtils::getRasterCoordinate($chunkX * 16 + $X + self::MYPLOT_OFFSET, $this->roadSize + $this->plotSize);

            for ($Z = 0; $Z < Chunk::EDGE_LENGTH; $Z++) {
                $z = CoordinateUtils::getRasterCoordinate($chunkZ * 16 + $Z + self::MYPLOT_OFFSET, $this->roadSize + $this->plotSize);

                $chunk->setBiomeId($X, $Z, $this->biomeID);
                if ($x < $this->roadSize || $z < $this->roadSize) {
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

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ) : void {
    }
}