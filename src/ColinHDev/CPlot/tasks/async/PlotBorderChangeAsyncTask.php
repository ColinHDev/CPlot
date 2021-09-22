<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\math\CoordinateUtils;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\worlds\schematics\Schematic;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\utils\SubChunkExplorerStatus;
use pocketmine\world\World;

class PlotBorderChangeAsyncTask extends ChunkModifyingAsyncTask {

    private string $worldSettings;
    private string $plot;
    private int $blockFullID;

    public function __construct(WorldSettings $worldSettings, Plot $plot, Block $block) {
        $this->startTime();
        $this->worldSettings = serialize($worldSettings->toArray());
        $this->plot = serialize($plot);
        $this->blockFullID = $block->getFullId();
    }

    public function onRun() : void {
        $worldSettings = WorldSettings::fromArray(unserialize($this->worldSettings, ["allowed_classes" => false]));
        /** @var Plot $plot */
        $plot = unserialize($this->plot, ["allowed_classes" => [Plot::class]]);

        /** @var Area[] $borderAreasToChange */
        $borderAreasToChange = [];
        /** @var Area[] $borderAreasToReset */
        $borderAreasToReset = [];

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
                    $borderAreaToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $borderAreaToChangeZMin = $plotPos->getZ() - 1;
                } else {
                    $borderAreaToChangeXMin = $plotPos->getX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getZ() - 1,
                        $plotPos->getX() - 2,
                        $plotPos->getZ() - 1
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderAreaToChangeZMax = $plotPos->getZ() - 1;
                } else {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getZ() - 1,
                        $plotPos->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getZ() - 1
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

            if (!$plot->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest)) {
                    $borderAreaToChangeXMin = $plotPos->getX() - $worldSettings->getRoadSize();
                    $borderAreaToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getZ() + $worldSettings->getPlotSize(),
                        $plotPos->getX() - 2,
                        $plotPos->getZ() + $worldSettings->getPlotSize()
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInEast)) {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();
                } else {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getZ() + $worldSettings->getPlotSize(),
                        $plotPos->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPos->getZ() + $worldSettings->getPlotSize()
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

            if (!$plot->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getX() - 1;
                    $borderAreaToChangeZMin = $plotPos->getZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getX() - 1,
                        $plotPos->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getX() - 1,
                        $plotPos->getZ() - 2
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderAreaToChangeXMax = $plotPos->getX() - 1;
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getX() - 1;
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getX() - 1,
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getX() - 1,
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
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

            if (!$plot->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth)) {
                    $borderAreaToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMin = $plotPos->getZ() - $worldSettings->getRoadSize();
                } else {
                    $borderAreaToChangeXMin = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMin = $plotPos->getZ() - 1;

                    $borderAreaToReset = new Area(
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() - 2
                    );
                    $key = $borderAreaToReset->toString();
                    if (!isset($borderAreasToReset[$key])) {
                        $borderAreasToReset[$key] = $borderAreaToReset;
                    }
                }
                if ($plot->isMerged($plotInSouth)) {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $borderAreaToChangeXMax = $plotPos->getX() + $worldSettings->getPlotSize();
                    $borderAreaToChangeZMax = $plotPos->getZ() + $worldSettings->getPlotSize();

                    $borderAreaToReset = new Area(
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPos->getX() + $worldSettings->getPlotSize(),
                        $plotPos->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
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
        if ($worldSettings->getRoadSchematic() !== "default") {
            $schematicRoad = new Schematic($worldSettings->getRoadSchematic(), "plugin_data" . DIRECTORY_SEPARATOR . "CPlot" . DIRECTORY_SEPARATOR . "schematics" . DIRECTORY_SEPARATOR . $worldSettings->getRoadSchematic() . "." . Schematic::FILE_EXTENSION);
            if (!$schematicRoad->loadFromFile()) {
                $schematicRoad = null;
            }
        }

        while ($this->chunks === null);

        $world = $this->getChunkManager();
        $explorer = new SubChunkExplorer($world);
        $finishedChunks = [];
        $y = $worldSettings->getGroundSize() + 1;
        $yInChunk = $y & 0x0f;
        foreach ($chunks as $chunkHash => $blockHashs) {
            World::getXZ($chunkHash, $chunkX, $chunkZ);

            if (isset($blockHashs["borderChange"])) {
                foreach ($blockHashs["borderChange"] as $blockHash) {
                    World::getXZ($blockHash, $xInChunk, $zInChunk);
                    $x = CoordinateUtils::getCoordinateFromChunk($chunkX, $xInChunk);
                    $z = CoordinateUtils::getCoordinateFromChunk($chunkZ, $zInChunk);
                    switch ($explorer->moveTo($x, $y, $z)) {
                        case SubChunkExplorerStatus::OK:
                        case SubChunkExplorerStatus::MOVED:
                            $explorer->currentSubChunk->setFullBlock(
                                $xInChunk,
                                $yInChunk,
                                $zInChunk,
                                $this->blockFullID
                            );
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
                        switch ($explorer->moveTo($x, $y, $z)) {
                            case SubChunkExplorerStatus::OK:
                            case SubChunkExplorerStatus::MOVED:
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $yInChunk,
                                    $zInChunk,
                                    $schematicRoad->getFullBlock($xRaster, $y, $zRaster)
                                );
                        }
                    } else {
                        switch ($explorer->moveTo($x, $y, $z)) {
                            case SubChunkExplorerStatus::OK:
                            case SubChunkExplorerStatus::MOVED:
                                $explorer->currentSubChunk->setFullBlock(
                                    $xInChunk,
                                    $yInChunk,
                                    $zInChunk,
                                    0
                                );
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