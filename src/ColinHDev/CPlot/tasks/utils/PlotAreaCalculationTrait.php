<?php

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

        $plots = array_merge([$originPlot], $originPlot->getMergePlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $area = new Area(
                $plotPosition->getFloorX(),
                $plotPosition->getFloorZ(),
                ($plotPosition->getFloorX() + $worldSettings->getPlotSize() - 1),
                ($plotPosition->getFloorZ() + $worldSettings->getPlotSize() - 1),
            );
            $areas[$area->toString()] = $area;
        }

        return $areas;
    }
}