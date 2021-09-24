<?php

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;

trait PlotAreaCalculationTrait {

    /**
     * @return Area[]
     * Returns all base areas of a plot. This doesn't include areas of merged roads.
     */
    private function calculateBasePlotAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergedPlots() ?? []);
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