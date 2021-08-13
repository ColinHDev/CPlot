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
            $plotPos = $mergedPlot->getPositionNonNull($worldSettings->getSizeRoad(), $worldSettings->getSizePlot(), $worldSettings->getSizeGround());

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
                ($plotPos->getFloorX() + $worldSettings->getSizePlot() - 1),
                ($plotPos->getFloorZ() + $worldSettings->getSizePlot() - 1),
            );
            $plotAreas[$plotArea->toString()] = $plotArea;

            if ($plot->isMerged($plotInNorth)) {
                if ($plot->isMerged($plotInWest) && $plot->isMerged($plotInNorthWest)) {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInNorthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() - 1;
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() - 1;
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInWest)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - ($worldSettings->getSizeRoad() - 1),
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
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 1);
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() - 1;
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + ($worldSettings->getSizePlot() + 1),
                        $plotPos->getFloorZ() - 1,
                        $plotPos->getFloorX() + ($worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 2)),
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
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $roadAreaZMin = $plotPos->getFloorZ() + $worldSettings->getSizePlot();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX();
                    $roadAreaZMin = $plotPos->getFloorZ() + $worldSettings->getSizePlot();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInSouthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInWest)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() + $worldSettings->getSizePlot();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() + $worldSettings->getSizePlot();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - ($worldSettings->getSizeRoad() - 1),
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorX() - 2,
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot()
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 1);
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot();
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + ($worldSettings->getSizePlot() + 1),
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorX() + ($worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 2)),
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot()
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
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX() - $worldSettings->getSizeRoad();
                    $roadAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthWest)) {
                    $roadAreaXMax = $plotPos->getFloorX() - 1;
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                } else {
                    $roadAreaXMax = $plotPos->getFloorX() - 1;
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() - ($worldSettings->getSizeRoad() - 1),
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
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() - 1;
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + 1),
                        $plotPos->getFloorX() - 1,
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 2)
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
                    $roadAreaXMin = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $roadAreaZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                } else {
                    $roadAreaXMin = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $roadAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthEast)) {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                }  else {
                    $roadAreaXMax = $plotPos->getFloorX() + ($worldSettings->getSizePlot() + $worldSettings->getSizeRoad() - 1);
                    $roadAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getSizePlot() - 1);
                }
                $roadArea = new Area($roadAreaXMin, $roadAreaZMin, $roadAreaXMax, $roadAreaZMax);
                $key = $roadArea->toString();
                if (!isset($roadAreas[$key])) {
                    $roadAreas[$key] = $roadArea;
                }
            } else {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - $worldSettings->getSizeRoad();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMin = $plotPos->getFloorZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorZ() - ($worldSettings->getSizeRoad() - 1),
                        $plotPos->getFloorX() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorZ() - 2
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getFloorX() + $worldSettings->getSizePlot();
                    $borderAreaToChangeZMax = $plotPos->getFloorZ() + $worldSettings->getSizePlot();

                    $borderAreaToReset = new Area(
                        $plotPos->getFloorX() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorZ() + ($worldSettings->getSizePlot() + 1),
                        $plotPos->getFloorX() + $worldSettings->getSizePlot(),
                        $plotPos->getFloorZ() + $worldSettings->getSizePlot() + ($worldSettings->getSizeRoad() - 2)
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
            if ($worldSettings->getSchematicRoad() !== "default") {
                $schematicRoad = new Schematic($worldSettings->getSchematicRoad(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getSchematicRoad() . "." . Schematic::FILE_EXTENSION);
                if (!$schematicRoad->loadFromFile()) {
                    $schematicRoad = null;
                }
            }
        } else {
            if ($worldSettings->getSchematicMergeRoad() !== "default") {
                $schematicRoad = new Schematic($worldSettings->getSchematicMergeRoad(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getSchematicMergeRoad() . "." . Schematic::FILE_EXTENSION);
                if (!$schematicRoad->loadFromFile()) {
                    $schematicRoad = null;
                }
            }
        }

        $schematicPlot = null;
        if ($worldSettings->getSchematicPlot() !== "default") {
            $schematicPlot = new Schematic($worldSettings->getSchematicPlot(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getSchematicPlot() . "." . Schematic::FILE_EXTENSION);
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
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot()) - $worldSettings->getSizeRoad();
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot()) - $worldSettings->getSizeRoad();
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
                                $fullBlock = $worldSettings->getBlockPlotBottom()->getFullId();
                            } else if ($y === $worldSettings->getSizeGround()) {
                                $fullBlock = $worldSettings->getBlockPlotFloor()->getFullId();
                            } else if ($y < $worldSettings->getSizeGround()) {
                                $fullBlock = $worldSettings->getBlockPlotFill()->getFullId();
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
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot());
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
                                    $fullBlock = $worldSettings->getBlockPlotBottom()->getFullId();
                                } else if ($y <= $worldSettings->getSizeGround()) {
                                    $fullBlock = $worldSettings->getBlockRoad()->getFullId();
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
                                    $fullBlock = $worldSettings->getBlockPlotBottom()->getFullId();
                                } else if ($y === $worldSettings->getSizeGround()) {
                                    $fullBlock = $worldSettings->getBlockPlotFloor()->getFullId();
                                } else if ($y < $worldSettings->getSizeGround()) {
                                    $fullBlock = $worldSettings->getBlockPlotFill()->getFullId();
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
                            $fullBlock = $worldSettings->getBlockPlotBottom()->getFullId();
                        } else if ($y === $worldSettings->getSizeGround() + 1) {
                            if ($plot->getOwnerUUID() === null) {
                                $fullBlock = $worldSettings->getBlockBorder()->getFullId();
                            } else {
                                $fullBlock = $worldSettings->getBlockBorderOnClaim()->getFullId();
                            }
                        } else if ($y <= $worldSettings->getSizeGround()) {
                            $fullBlock = $worldSettings->getBlockRoad()->getFullId();
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
                        $xRaster = CoordinateUtils::getRasterCoordinate($x, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot());
                        $zRaster = CoordinateUtils::getRasterCoordinate($z, $worldSettings->getSizeRoad() + $worldSettings->getSizePlot());
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
                                $fullBlock = $worldSettings->getBlockPlotBottom()->getFullId();
                            } else if ($y <= $worldSettings->getSizeGround()) {
                                $fullBlock = $worldSettings->getBlockRoad()->getFullId();
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