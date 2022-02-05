<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\math\Area;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\worlds\WorldSettings;

trait PlotAreaCalculationTrait {
    use AreaCalculationTrait;

    /**
     * @return Area[]
     * Returns all base areas of a plot. This doesn't include areas of merged roads.
     */
    private function calculateBasePlotAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots());
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getVector3NonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $plotPositionX = $plotPosition->getFloorX();
            $plotPositionZ = $plotPosition->getFloorZ();
            $area = new Area(
                $plotPositionX,
                $plotPositionZ,
                ($plotPositionX + $worldSettings->getPlotSize() - 1),
                ($plotPositionZ + $worldSettings->getPlotSize() - 1),
            );
            $areas[$area->toString()] = $area;
        }

        return $areas;
    }
}