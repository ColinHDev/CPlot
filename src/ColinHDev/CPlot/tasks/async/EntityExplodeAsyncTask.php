<?php

namespace ColinHDev\CPlot\tasks\async;

use ColinHDev\CPlot\tasks\utils\PlotAreaCalculationTrait;
use ColinHDev\CPlot\tasks\utils\RoadAreaCalculationTrait;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\world\World;

class EntityExplodeAsyncTask extends CPlotAsyncTask {
    use PlotAreaCalculationTrait;
    use RoadAreaCalculationTrait;

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

        $plotAreas = array_merge(
            $this->calculateBasePlotAreas($worldSettings, $plot),
            $this->calculateMergeRoadAreas($worldSettings, $plot)
        );

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