<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\math\CoordinateUtils;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\MergePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\tasks\utils\PlotBorderAreaCalculationTrait;
use ColinHDev\CPlot\tasks\utils\RoadAreaCalculationTrait;
use ColinHDev\CPlot\worlds\schematic\Schematic;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\format\SubChunk;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class PlotMergeAsyncTask extends ChunkModifyingAsyncTask {
    use PlotBorderAreaCalculationTrait;
    use RoadAreaCalculationTrait;

    private string $worldSettings;

    public function __construct(World $world, WorldSettings $worldSettings, Plot $plot, Plot $plotToMerge) {
        $this->worldSettings = serialize($worldSettings->toArray());

        $chunks = [];
        $this->getChunksFromAreas("road", $this->calculateNonMergeRoadAreas($worldSettings, $plot, $plotToMerge), $chunks);
        $plot = clone $plot;
        /** @var BasePlot $mergePlotToMerge */
        foreach (array_merge([$plotToMerge], $plotToMerge->getMergePlots()) as $mergePlotToMerge) {
            $plot->addMergePlot(MergePlot::fromBasePlot($mergePlotToMerge, $plot->getX(), $plot->getZ()));
        }
        $this->getChunksFromAreas("borderChange", $this->calculatePlotBorderAreas($worldSettings, $plot), $chunks);
        $this->getChunksFromAreas("borderReset", $this->calculatePlotBorderExtensionAreas($worldSettings, $plot), $chunks);

        parent::__construct($world, $chunks);
    }

    public function onRun() : void {
        /** @phpstan-var array{worldName: string, worldType: string, roadSchematic: string, mergeRoadSchematic: string, plotSchematic: string, roadSize: int, plotSize: int, groundSize: int, roadBlock: string, borderBlock: string, plotFloorBlock: string, plotFillBlock: string, plotBottomBlock: string} $worldSettingsArray */
        $worldSettingsArray = unserialize($this->worldSettings, ["allowed_classes" => false]);
        $worldSettings = WorldSettings::fromArray($worldSettingsArray);

        $schematicRoad = null;
        if ($worldSettings->getRoadSchematic() !== "default") {
            $schematicRoad = new Schematic($worldSettings->getRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getRoadSchematic() . "." . Schematic::FILE_EXTENSION);
            if (!$schematicRoad->loadFromFile()) {
                $schematicRoad = null;
            }
        }
        $schematicMergeRoad = null;
        if ($worldSettings->getMergeRoadSchematic() !== "default") {
            $schematicMergeRoad = new Schematic($worldSettings->getRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getRoadSchematic() . "." . Schematic::FILE_EXTENSION);
            if (!$schematicMergeRoad->loadFromFile()) {
                $schematicMergeRoad = null;
            }
        }

        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        /** @phpstan-var array<int, array<string, int[]>> $chunkAreas */
        $chunkAreas = unserialize($this->chunkAreas, ["allowed_classes" => false]);
        foreach ($chunkAreas as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            if (isset($blockHashs["road"])) {
                foreach ($blockHashs["road"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicMergeRoad !== null) {
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $explorer->moveTo($x, $y, $z);
                            if ($explorer->currentSubChunk instanceof SubChunk) {
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $y & 0x0f,
                                    $zInChunk,
                                    $schematicMergeRoad->getFullBlock($xRaster, $y, $zRaster)
                                );
                            }
                        }
                    } else {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            if ($y === $world->getMinY()) {
                                $fullBlock = $worldSettings->getPlotBottomBlock()->getFullId();
                            } else if ($y === $worldSettings->getGroundSize()) {
                                $fullBlock = $worldSettings->getPlotFloorBlock()->getFullId();
                            } else if ($y < $worldSettings->getGroundSize()) {
                                $fullBlock = $worldSettings->getPlotFillBlock()->getFullId();
                            } else {
                                $fullBlock = 0;
                            }
                            $explorer->moveTo($x, $y, $z);
                            if ($explorer->currentSubChunk instanceof SubChunk) {
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $y & 0x0f,
                                    $zInChunk,
                                    $fullBlock
                                );
                            }
                        }
                    }
                }
            }

            if (isset($blockHashs["borderChange"])) {
                foreach ($blockHashs["borderChange"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                        if ($y === $world->getMinY()) {
                            $fullBlock = $worldSettings->getPlotBottomBlock()->getFullId();
                        } else if ($y === $worldSettings->getGroundSize() + 1) {
                            $fullBlock = $worldSettings->getBorderBlock()->getFullId();
                        } else if ($y <= $worldSettings->getGroundSize()) {
                            $fullBlock = $worldSettings->getRoadBlock()->getFullId();
                        } else {
                            $fullBlock = 0;
                        }
                        $explorer->moveTo($x, $y, $z);
                        if ($explorer->currentSubChunk instanceof SubChunk) {
                            $explorer->currentSubChunk->setFullBlock(
                                $xInChunk,
                                $y & 0x0f,
                                $zInChunk,
                                $fullBlock
                            );
                        }
                    }
                }
            }

            if (isset($blockHashs["borderReset"])) {
                foreach ($blockHashs["borderReset"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicRoad !== null) {
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            $explorer->moveTo($x, $y, $z);
                            if ($explorer->currentSubChunk instanceof SubChunk) {
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $y & 0x0f,
                                    $zInChunk,
                                    $schematicRoad->getFullBlock($xRaster, $y, $zRaster)
                                );
                            }
                        }
                    } else {
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            if ($y === $world->getMinY()) {
                                $fullBlock = $worldSettings->getPlotBottomBlock()->getFullId();
                            } else if ($y <= $worldSettings->getGroundSize()) {
                                $fullBlock = $worldSettings->getRoadBlock()->getFullId();
                            } else {
                                $fullBlock = 0;
                            }
                            $explorer->moveTo($x, $y, $z);
                            if ($explorer->currentSubChunk instanceof SubChunk) {
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $y & 0x0f,
                                    $zInChunk,
                                    $fullBlock
                                );
                            }
                        }
                    }
                }
            }

            $chunk = $world->getChunk($chunkX, $chunkZ);
            assert($chunk instanceof Chunk);
            $finishedChunks[$chunkHash] = FastChunkSerializer::serializeTerrain($chunk);
        }

        $this->chunks = serialize($finishedChunks);
    }
}