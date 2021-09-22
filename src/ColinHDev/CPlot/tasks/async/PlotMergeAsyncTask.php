<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlotAPI\worlds\schematics\Schematic;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\math\CoordinateUtils;
use ColinHDev\CPlotAPI\MergedPlot;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\math\Facing;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;

class PlotMergeAsyncTask extends ChunkModifyingAsyncTask {

    private string $worldSettings;
    private string $plot;
    private string $plotToMerge;

    public function __construct(WorldSettings $worldSettings, Plot $plot, Plot $plotToMerge) {
        $this->startTime();
        $this->worldSettings = serialize($worldSettings->toArray());
        $this->plot = serialize($plot);
        $this->plotToMerge = serialize($plotToMerge);
    }

    public function onRun() : void {
        $worldSettings = WorldSettings::fromArray(unserialize($this->worldSettings, ["allowed_classes" => false]));
        /** @var Plot $plot */
        $plot = unserialize($this->plot, ["allowed_classes" => [Plot::class]]);
        /** @var Plot $plotToMerge */
        $plotToMerge = unserialize($this->plotToMerge, ["allowed_classes" => [Plot::class]]);

        /** @var Area[] $roadPositionsToChange */
        $roadPositionsToChange = [];
        /** @var BasePlot $alreadyMergedPlot */
        foreach (array_merge([$plot], $plot->getMergedPlots()) as $alreadyMergedPlot) {
            $plotPos = $alreadyMergedPlot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $alreadyMergedPlot->getSide(Facing::NORTH);
            $plotInNorthWest = $plotInNorth->getSide(Facing::WEST);
            $plotInNorthEast = $plotInNorth->getSide(Facing::EAST);
            $plotInSouth = $alreadyMergedPlot->getSide(Facing::SOUTH);
            $plotInSouthWest = $plotInSouth->getSide(Facing::WEST);
            $plotInSouthEast = $plotInSouth->getSide(Facing::EAST);
            $plotInWest = $alreadyMergedPlot->getSide(Facing::WEST);
            $plotInEast = $alreadyMergedPlot->getSide(Facing::EAST);

            if (!$plot->isMerged($plotInNorth) && $plotToMerge->isMerged($plotInNorth)) {
                if ($plot->isMerged($plotInWest) && ($plot->isMerged($plotInNorthWest) || $plotToMerge->isMerged($plotInNorthWest))) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else if ($plotToMerge->isMerged($plotInWest) && $plotToMerge->isMerged($plotInNorthWest)) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $roadPositionToChangeXMin = $plotPos->getX();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                }
                if ($plot->isMerged($plotInEast) && ($plot->isMerged($plotInNorthEast) || $plotToMerge->isMerged($plotInNorthEast))) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() - 1;
                } else if ($plotToMerge->isMerged($plotInEast) && $plotToMerge->isMerged($plotInNorthEast)) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() - 1;
                } else {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() - 1;
                }
                $roadPositionToChange = new Area($roadPositionToChangeXMin, $roadPositionToChangeZMin, $roadPositionToChangeXMax, $roadPositionToChangeZMax);
                $key = $roadPositionToChange->toString();
                if (!isset($roadPositionsToChange[$key])) {
                    $roadPositionsToChange[$key] = $roadPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInSouth) && $plotToMerge->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest) && ($plot->isMerged($plotInSouthWest) || $plotToMerge->isMerged($plotInSouthWest))) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else if ($plotToMerge->isMerged($plotInWest) && $plotToMerge->isMerged($plotInSouthWest)) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else {
                    $roadPositionToChangeXMin = $plotPos->getX();
                    $roadPositionToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();
                }
                if ($plot->isMerged($plotInEast) && ($plot->isMerged($plotInSouthEast) || $plotToMerge->isMerged($plotInSouthEast))) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else if ($plotToMerge->isMerged($plotInWest) && $plotToMerge->isMerged($plotInSouthEast)) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                }
                $roadPositionToChange = new Area($roadPositionToChangeXMin, $roadPositionToChangeZMin, $roadPositionToChangeXMax, $roadPositionToChangeZMax);
                $key = $roadPositionToChange->toString();
                if (!isset($roadPositionsToChange[$key])) {
                    $roadPositionsToChange[$key] = $roadPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInWest) && $plotToMerge->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth) && ($plot->isMerged($plotInNorthWest) || $plotToMerge->isMerged($plotInNorthWest))) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else if ($plotToMerge->isMerged($plotInNorth) && $plotToMerge->isMerged($plotInNorthWest)) {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $roadPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $roadPositionToChangeZMin = $plotPos->getZ();
                }
                if ($plot->isMerged($plotInSouth) && ($plot->isMerged($plotInSouthWest) || $plotToMerge->isMerged($plotInSouthWest))) {
                    $roadPositionToChangeXMax = $plotPos->getX() - 1;
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else if ($plotToMerge->isMerged($plotInSouth) && $plotToMerge->isMerged($plotInSouthWest)) {
                    $roadPositionToChangeXMax = $plotPos->getX() - 1;
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $roadPositionToChangeXMax = $plotPos->getX() - 1;
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() - 1);
                }
                $roadPositionToChange = new Area($roadPositionToChangeXMin, $roadPositionToChangeZMin, $roadPositionToChangeXMax, $roadPositionToChangeZMax);
                $key = $roadPositionToChange->toString();
                if (!isset($roadPositionsToChange[$key])) {
                    $roadPositionsToChange[$key] = $roadPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInEast) && $plotToMerge->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth) && ($plot->isMerged($plotInNorthEast) || $plotToMerge->isMerged($plotInNorthEast))) {
                    $roadPositionToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else if ($plotToMerge->isMerged($plotInNorth) && $plotToMerge->isMerged($plotInNorthEast)) {
                    $roadPositionToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $roadPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $roadPositionToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $roadPositionToChangeZMin = $plotPos->getZ();
                }
                if ($plot->isMerged($plotInSouth) && ($plot->isMerged($plotInSouthEast) || $plotToMerge->isMerged($plotInSouthEast))) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else if ($plotToMerge->isMerged($plotInSouth) && $plotToMerge->isMerged($plotInSouthEast)) {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $roadPositionToChangeXMax = $plotPos->getX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $roadPositionToChangeZMax = $plotPos->getZ() + ($worldSettings->getPlotSize() - 1);
                }
                $roadPositionToChange = new Area($roadPositionToChangeXMin, $roadPositionToChangeZMin, $roadPositionToChangeXMax, $roadPositionToChangeZMax);
                $key = $roadPositionToChange->toString();
                if (!isset($roadPositionsToChange[$key])) {
                    $roadPositionsToChange[$key] = $roadPositionToChange;
                }
            }
        }

        /** @var Area[] $borderPositionsToChange */
        $borderPositionsToChange = [];
        /** @var Area[] $borderPositionsToReset */
        $borderPositionsToReset = [];
        /** @var BasePlot $mergedPlotToMerge */
        foreach (array_merge([$plotToMerge], $plotToMerge->getMergedPlots()) as $mergedPlotToMerge) {
            $plot->addMerge(MergedPlot::fromBasePlot($mergedPlotToMerge, $plot->getX(), $plot->getZ()));
        }
        $plots = array_merge([$plot], $plot->getMergedPlots());
        /** @var BasePlot $mergedPlot */
        foreach ($plots as $mergedPlot) {
            $plotPos = $mergedPlot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $mergedPlot->getSide(Facing::NORTH);
            $plotInSouth = $mergedPlot->getSide(Facing::SOUTH);
            $plotInWest = $mergedPlot->getSide(Facing::WEST);
            $plotInEast = $mergedPlot->getSide(Facing::EAST);

            if (!$plot->isMerged($plotInNorth)) {
                if ($plot->isMerged($plotInWest)) {
                    $borderPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $borderPositionToChangeZMin = $plotPos->getZ() - 1;
                } else {
                    $borderPositionToChangeXMin = $plotPos->getX() - 1;
                    $borderPositionToChangeZMin = $plotPos->getZ() - 1;

                    $borderPositionToReset = new Area(
                        $plotPos->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getZ() - 1,
                        $plotPos->getX() - 2,
                        $plotPos->getZ() - 1
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderPositionToChangeZMax = $plotPos->getZ() - 1;
                } else {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMax = $plotPos->getZ() - 1;

                    $borderPositionToReset = new Area(
                        $plotPos->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getZ() - 1,
                        $plotPos->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getZ() - 1
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                $borderPositionToChange = new Area($borderPositionToChangeXMin, $borderPositionToChangeZMin, $borderPositionToChangeXMax, $borderPositionToChangeZMax);
                $key = $borderPositionToChange->toString();
                if (!isset($borderPositionsToChange[$key])) {
                    $borderPositionsToChange[$key] = $borderPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest)) {
                    $borderPositionToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $borderPositionToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else {
                    $borderPositionToChangeXMin = $plotPos->getX() - 1;
                    $borderPositionToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderPositionToReset = new Area(
                        $plotPos->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getZ() + $worldSettings->getPlotSize(),
                        $plotPos->getX() - 2,
                        $plotPos->getZ() + $worldSettings->getPlotSize()
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderPositionToReset = new Area(
                        $plotPos->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getZ() + $worldSettings->getPlotSize(),
                        $plotPos->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getZ() + $worldSettings->getPlotSize()
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                $borderPositionToChange = new Area($borderPositionToChangeXMin, $borderPositionToChangeZMin, $borderPositionToChangeXMax, $borderPositionToChangeZMax);
                $key = $borderPositionToChange->toString();
                if (!isset($borderPositionsToChange[$key])) {
                    $borderPositionsToChange[$key] = $borderPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth)) {
                    $borderPositionToChangeXMin = $plotPos->getX() - 1;
                    $borderPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $borderPositionToChangeXMin = $plotPos->getX() - 1;
                    $borderPositionToChangeZMin = $plotPos->getZ() - 1;

                    $borderPositionToReset = new Area(
                        $plotPos->getX() - 1,
                        $plotPos->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getX() - 1,
                        $plotPos->getZ() - 2
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderPositionToChangeXMax = $plotPos->getX() - 1;
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderPositionToChangeXMax = $plotPos->getX() - 1;
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderPositionToReset = new Area(
                        $plotPos->getX() - 1,
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getX() - 1,
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                $borderPositionToChange = new Area($borderPositionToChangeXMin, $borderPositionToChangeZMin, $borderPositionToChangeXMax, $borderPositionToChangeZMax);
                $key = $borderPositionToChange->toString();
                if (!isset($borderPositionsToChange[$key])) {
                    $borderPositionsToChange[$key] = $borderPositionToChange;
                }
            }

            if (!$plot->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth)) {
                    $borderPositionToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $borderPositionToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMin = $plotPos->getZ() - 1;

                    $borderPositionToReset = new Area(
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() - 2
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderPositionToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderPositionToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderPositionToReset = new Area(
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
                    );
                    $key = $borderPositionToReset->toString();
                    if (!isset($borderPositionsToReset[$key])) {
                        $borderPositionsToReset[$key] = $borderPositionToReset;
                    }
                }
                $borderPositionToChange = new Area($borderPositionToChangeXMin, $borderPositionToChangeZMin, $borderPositionToChangeXMax, $borderPositionToChangeZMax);
                $key = $borderPositionToChange->toString();
                if (!isset($borderPositionsToChange[$key])) {
                    $borderPositionsToChange[$key] = $borderPositionToChange;
                }
            }
        }

        $chunks = [];
        foreach ($roadPositionsToChange as $area) {
            for ($x = $area->getXMin(); $x <= $area->getXMax(); $x++) {
                for ($z = $area->getZMin(); $z <= $area->getZMax(); $z++) {
                    $chunkHash = World::chunkHash($x >> 4, $z >> 4);
                    $blockHash = World::chunkHash($x & 0x0f, $z & 0x0f);
                    if (!isset($chunks[$chunkHash])) {
                        $chunks[$chunkHash] = [];
                        $chunks[$chunkHash]["roadChange"] = [];
                    } else if (!isset($chunks[$chunkHash]["roadChange"])) {
                        $chunks[$chunkHash]["roadChange"] = [];
                    } else if (in_array($blockHash, $chunks[$chunkHash]["roadChange"], true)) continue;
                    $chunks[$chunkHash]["roadChange"][] = $blockHash;
                }
            }
        }
        foreach ($borderPositionsToChange as $area) {
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
        foreach ($borderPositionsToReset as $area) {
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

        while ($this->chunks === null);

        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        foreach ($chunks as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            if (isset($blockHashs["roadChange"])) {
                foreach ($blockHashs["roadChange"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    if ($schematicMergeRoad !== null) {
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