<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\math\Facing;
use pocketmine\world\World;

class EntityExplodeAsyncTask extends CPlotAsyncTask {

    private string $worldSettings;
    private string $plot;
    private string $affectedBlocks;

    /**
     * @param int[] $affectedBlocks
     */
    public function __construct(WorldSettings $worldSettings, Plot $plot, array $affectedBlocks) {
        $this->startTime();
        $this->worldSettings = serialize($worldSettings->toArray());
        $this->plot = serialize($plot);
        $this->affectedBlocks = serialize($affectedBlocks);
    }

    public function onRun() : void {
        $worldSettings = WorldSettings::fromArray(unserialize($this->worldSettings, ["allowed_classes" => false]));
        /** @var Plot $plot */
        $plot = unserialize($this->plot, ["allowed_classes" => [Plot::class]]);
        /** @var int[] $affectedBlocks */
        $affectedBlocks = unserialize($this->affectedBlocks, ["allowed_classes" => false]);

        /** @var Area[] $plotAreas */
        $plotAreas = [];
        /** @var BasePlot $alreadyMergedPlot */
        foreach (array_merge([$plot], $plot->getMergedPlots()) as $mergedPlot) {
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
                    $plotAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $plotAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $plotAreaXMin = $plotPos->getFloorX();
                    $plotAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInNorthEast)) {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() - 1;
                } else {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() - 1;
                }
                $plotArea = new Area($plotAreaXMin, $plotAreaZMin, $plotAreaXMax, $plotAreaZMax);
                $key = $plotArea->toString();
                if (!isset($plotAreas[$key])) {
                    $plotAreas[$key] = $plotArea;
                }
            }

            if ($plot->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest) && $plot->isMerged($plotInSouthWest)) {
                    $plotAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $plotAreaZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                } else {
                    $plotAreaXMin = $plotPos->getFloorX();
                    $plotAreaZMin = $plotPos->getFloorZ() + $worldSettings->getPlotSize();
                }
                if ($plot->isMerged($plotInEast) && $plot->isMerged($plotInSouthEast)) {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                }
                $plotArea = new Area($plotAreaXMin, $plotAreaZMin, $plotAreaXMax, $plotAreaZMax);
                $key = $plotArea->toString();
                if (!isset($plotAreas[$key])) {
                    $plotAreas[$key] = $plotArea;
                }
            }

            if ($plot->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth) && $plot->isMerged($plotInNorthWest)) {
                    $plotAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $plotAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $plotAreaXMin = $plotPos->getFloorX() - $worldSettings->getRoadSize();
                    $plotAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthWest)) {
                    $plotAreaXMax = $plotPos->getFloorX() - 1;
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                } else {
                    $plotAreaXMax = $plotPos->getFloorX() - 1;
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() - 1);
                }
                $plotArea = new Area($plotAreaXMin, $plotAreaZMin, $plotAreaXMax, $plotAreaZMax);
                $key = $plotArea->toString();
                if (!isset($plotAreas[$key])) {
                    $plotAreas[$key] = $plotArea;
                }
            }

            if ($plot->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth) && $plot->isMerged($plotInNorthEast)) {
                    $plotAreaXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $plotAreaZMin = $plotPos->getFloorZ() - $worldSettings->getRoadSize();
                } else {
                    $plotAreaXMin = $plotPos->getFloorX() + $worldSettings->getPlotSize();
                    $plotAreaZMin = $plotPos->getFloorZ();
                }
                if ($plot->isMerged($plotInSouth) && $plot->isMerged($plotInSouthEast)) {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                }  else {
                    $plotAreaXMax = $plotPos->getFloorX() + ($worldSettings->getPlotSize() + $worldSettings->getRoadSize() - 1);
                    $plotAreaZMax = $plotPos->getFloorZ() + ($worldSettings->getPlotSize() - 1);
                }
                $plotArea = new Area($plotAreaXMin, $plotAreaZMin, $plotAreaXMax, $plotAreaZMax);
                $key = $plotArea->toString();
                if (!isset($plotAreas[$key])) {
                    $plotAreas[$key] = $plotArea;
                }
            }
        }

        $newAffectedBlocks = [];
        foreach ($affectedBlocks as $positionHash => $fullId) {
            World::getBlockXYZ($positionHash, $positionX, $positionY, $positionZ);
            foreach ($plotAreas as $plotArea) {
                if (!$plotArea->isInside($positionX, $positionZ)) continue;
                $newAffectedBlocks[$positionHash] = $fullId;
            }
        }

        $this->setResult([
            $newAffectedBlocks,
            count($affectedBlocks),
            count($newAffectedBlocks)
        ]);
    }
}