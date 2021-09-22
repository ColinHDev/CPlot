<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\math\CoordinateUtils;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\worlds\schematics\Schematic;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\math\Facing;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;

class PlotClearAsyncTask extends ChunkModifyingAsyncTask {

    private string $worldSettings;
    private string $plot;

    public function __construct(WorldSettings $worldSettings, Plot $plot) {
        $this->startTime();
        $this->worldSettings = serialize($worldSettings->toArray());
        $this->plot = serialize($plot);
    }

    public function onRun() : void {
        $worldSettings = WorldSettings::fromArray(unserialize($this->worldSettings, ["allowed_classes" => false]));
        /** @var Plot $plot */
        $plot = unserialize($this->plot, ["allowed_classes" => [Plot::class]]);

        /** @var Area[] $plotAreas */
        $plotAreas = [];
        /** @var Area[] $roadAreas */
        $roadAreas = [];
        /** @var Area[] $borderAreasToChange */
        $borderAreasToChange = [];
        /** @var Area[] $borderAreasToReset */
        $borderAreasToReset = [];

        $plots = array_merge([$plot], $plot->getMergedPlots());
        /** @var BasePlot $mergedPlot */
        foreach ($plots as $mergedPlot) {
            $plotPos = $mergedPlot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $mergedPlot->getSide(Facing::NORTH);
            $plotInNorthWest = $plotInNorth->getSide(Facing::WEST);
            $plotInNorthEast = $plotInNorth->getSide(Facing::EAST);
            $plotInSouth = $mergedPlot->getSide(Facing::SOUTH);
            $plotInSouthWest = $plotInSouth->getSide(Facing::WEST);
            $plotInSouthEast = $plotInSouth->getSide(Facing::EAST);
            $plotInWest = $mergedPlot->getSide(Facing::WEST);
            $plotInEast = $mergedPlot->getSide(Facing::EAST);

            $plotArea = new Area(
                $plotPos->getFloorX(),
                $plotPos->getFloorZ(),
                ($plotPos->getFloorX() + $worldSettings->getPlotSize() - 1),
                ($plotPos->getFloorZ() + $worldSettings->getPlotSize() - 1),
            );
            $plotAreas[$plotArea->toString()] = $plotArea;

            if ($plot->isMerged($plotInNorth)) {
                if ($plot->isMerged($plotInWest) && $plot->isMerged($plotInNorthWest)) {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInNorthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() - 1;
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() - 1;
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInWest)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getFloorZ() - 1,
                        $plotPos->getFloorX() - 2,
                        $plotPos->getFloorZ() - 1
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() - 1;
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getFloorZ() - 1,
                        $plotPos->getFloorX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getFloorZ() - 1
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                $borderAreaToChange = new Area($borderAreaToChangeXMin, $borderAreaToChangeZMin, $borderAreaToChangeXMax, $borderAreaToChangeZMax);
                $key = $borderAreaToChange->toString();
                if (!isset($borderAreasToChange[$key])) {
                    $borderAreasToChange[$key] = $borderAreaToChange;
                }
            }

            if ($plot->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest) && $plot->isMerged($plotInSouthWest)) {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $roadAreaZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX();
                    $roadAreaZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInSouthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInWest)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorX() - 2,
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize()
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize()
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                $borderAreaToChange = new Area($borderAreaToChangeXMin, $borderAreaToChangeZMin, $borderAreaToChangeXMax, $borderAreaToChangeZMax);
                $key = $borderAreaToChange->toString();
                if (!isset($borderAreasToChange[$key])) {
                    $borderAreasToChange[$key] = $borderAreaToChange;
                }
            }

            if ($plot->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth) && $plot->isMerged($plotInNorthWest)) {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $roadAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthWest)) {
                    $roadAreaXMax = $plotPos->getFloorX() - 1;
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() - 1;
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() - 2
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                $borderAreaToChange = new Area($borderAreaToChangeXMin, $borderAreaToChangeZMin, $borderAreaToChangeXMax, $borderAreaToChangeZMax);
                $key = $borderAreaToChange->toString();
                if (!isset($borderAreasToChange[$key])) {
                    $borderAreasToChange[$key] = $borderAreaToChange;
                }
            }

            if ($plot->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth) && $plot->isMerged($plotInNorthEast)) {
                    $roadAreaXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $roadAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                }  else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getFloorX() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorZ() - 2
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getFloorX() + $worldSettings->getPlotSize(),
                        $plotPos->getFloorZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                $borderAreaToChange = new Area($borderAreaToChangeXMin, $borderAreaToChangeZMin, $borderAreaToChangeXMax, $borderAreaToChangeZMax);
                $key = $borderAreaToChange->toString();
                if (!isset($borderAreasToChange[$key])) {
                    $borderAreasToChange[$key] = $borderAreaToChange;
                }
            }
        }

        $chunks = [];
        foreach ($plotAreas as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash]["plot"] = [];
                    } else if (!isset($chunks[$chunkHash]["plot"])) {
                        $chunks[$chunkHash]["plot"] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash]["plot"], true)) continue;
                    $chunks[$chunkHash]["plot"][] = $blockHash;
                }
            }
        }
        foreach ($roadAreas as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash]["road"] = [];
                    } else if (!isset($chunks[$chunkHash]["road"])) {
                        $chunks[$chunkHash]["road"] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash]["road"], true)) continue;
                    $chunks[$chunkHash]["road"][] = $blockHash;
                }
            }
        }
        foreach ($borderAreasToChange as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash]["borderChange"] = [];
                    } else if (!isset($chunks[$chunkHash]["borderChange"])) {
                        $chunks[$chunkHash]["borderChange"] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash]["borderChange"], true)) continue;
                    $chunks[$chunkHash]["borderChange"][] = $blockHash;
                }
            }
        }
        foreach ($borderAreasToReset as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash]["borderReset"] = [];
                    } else if (!isset($chunks[$chunkHash]["borderReset"])) {
                        $chunks[$chunkHash]["borderReset"] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash]["borderReset"], true)) continue;
                    $chunks[$chunkHash]["borderReset"][] = $blockHash;
                }
            }
        }

        $this->publishProgress($chunks);

        $plotCount = count($plots);

        $schematicRoad = null;
        if ($plot->getOwnerUUID() === null) {
            if ($worldSettings->getRoadSchematic() !== "default") {
                $schematicRoad = new Schematic($worldSettings->getRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getRoadSchematic() . "." . Schematic::FILE_EXTENSION);
                if (!$schematicRoad->loadFromFile()) {
                    $schematicRoad = null;
                }
            }
        } else {
            if ($worldSettings->getMergeRoadSchematic() !== "default") {
                $schematicRoad = new Schematic($worldSettings->getMergeRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getMergeRoadSchematic() . "." . Schematic::FILE_EXTENSION);
                if (!$schematicRoad->loadFromFile()) {
                    $schematicRoad = null;
                }
            }
        }

        $schematicPlot = null;
        if ($worldSettings->getPlotSchematic() !== "default") {
            $schematicPlot = new Schematic($worldSettings->getPlotSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getPlotSchematic() . "." . Schematic::FILE_EXTENSION);
            if (!$schematicPlot->loadFromFile()) {
                $schematicPlot = null;
            }
        }

        while ($this->chunks === null);

        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        foreach ($chunks as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            if (isset($blockHashs["plot"])) {
                foreach ($blockHashs["plot"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicPlot !== null) {
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getRoadSize() + $worldSettings->getPlotSize()) - $worldSettings->getRoadSize();
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getRoadSize() + $worldSettings->getPlotSize()) - $worldSettings->getRoadSize();
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $explorer->currentSubChunk->setFullBlock(
                                        $xInChunk,
                                        $y & 0x0f,
                                        $zInChunk,
                                        $schematicPlot->getFullBlock($xRaster, $y, $zRaster)
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
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
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

            if (isset($blockHashs["road"])) {
                foreach ($blockHashs["road"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicRoad !== null) {
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getRoadSize() + $worldSettings->getPlotSize());
                        for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
                                    $explorer->currentSubChunk->setFullBlock(
                                        $xInChunk,
                                        $y & 0x0f,
                                        $zInChunk,
                                        $schematicRoad->getFullBlock($xRaster, $y, $zRaster)
                                    );
                            }
                        }
                    } else {
                        if ($plot->getOwnerUUID() === null) {
                            for ($y = $world->getMinY(); $y < $world->getMaxY(); $y++) {
                                if ($y === $world->getMinY()) {
                                    $fullBlock = $worldSettings->getPlotBottomBlock()->getFullId();
                                } else if ($y <= $worldSettings->getGroundSize()) {
                                    $fullBlock = $worldSettings->getRoadBlock()->getFullId();
                                } else {
                                    $fullBlock = 0;
                                }
                                switch ($explorer->moveTo($x, $y, $z)) {
                                    case SubChunkExplorerStatus::OK:
                                    case SubChunkExplorerStatus::MOVED:
                                        $explorer->currentSubChunk->setFullBlock(
                                            $xInChunk,
                                            $y & 0x0f,
                                            $zInChunk,
                                            $fullBlock
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
                                switch ($explorer->moveTo($x, $y, $z)) {
                                    case SubChunkExplorerStatus::OK:
                                    case SubChunkExplorerStatus::MOVED:
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
                            if ($plot->getOwnerUUID() === null) {
                                $fullBlock = $worldSettings->getBorderBlock()->getFullId();
                            } else {
                                $fullBlock = $worldSettings->getBorderBlockOnClaim()->getFullId();
                            }
                        } else if ($y <= $worldSettings->getGroundSize()) {
                            $fullBlock = $worldSettings->getRoadBlock()->getFullId();
                        } else {
                            $fullBlock = 0;
                        }
                        switch ($explorer->moveTo($x, $y, $z)) {
                            case SubChunkExplorerStatus::OK:
                            case SubChunkExplorerStatus::MOVED:
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
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
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
                            switch ($explorer->moveTo($x, $y, $z)) {
                                case SubChunkExplorerStatus::OK:
                                case SubChunkExplorerStatus::MOVED:
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

            $finishedChunks[$chunkHash] = FastChunkSerializer::serializeWithoutLight($world->getChunk($chunkX, $chunkZ));
        }

        $this->chunks = serialize($finishedChunks);
        $this->setResult([$plotCount, $plots]);
    }
}