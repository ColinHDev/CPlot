<?php

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\math\Area;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\math\Facing;

trait PlotBorderAreaCalculationTrait {

    /**
     * @return Area[]
     * Returns all areas of a plot's border.
     */
    private function calculatePlotBorderAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergedPlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $plot->getSide(Facing::NORTH);
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            $plotInWest = $plot->getSide(Facing::WEST);
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$plot->isMerged($plotInNorth)) {
                if ($plot->isMerged($plotInWest)) {
                    $areaXMin = $plotPosition->getX() - $worldSettings->getRoadSize();
                    $areaZMin = $plotPosition->getZ() - 1;
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($plot->isMerged($plotInEast)) {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $areaZMax = $plotPosition->getZ() - 1;
                } else {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMax = $plotPosition->getZ() - 1;
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$plot->isMerged($plotInSouth)) {
                if ($plot->isMerged($plotInWest)) {
                    $areaXMin = $plotPosition->getX() - $worldSettings->getRoadSize();
                    $areaZMin = $plotPosition->getZ() + $worldSettings->getPlotSize();
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() + $worldSettings->getPlotSize();
                }
                if ($plot->isMerged($plotInEast)) {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize();
                } else {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize();
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$plot->isMerged($plotInWest)) {
                if ($plot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($plot->isMerged($plotInSouth)) {
                    $areaXMax = $plotPosition->getX() - 1;
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $areaXMax = $plotPosition->getX() - 1;
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize();
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$plot->isMerged($plotInEast)) {
                if ($plot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMin = $plotPosition->getZ() - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($plot->isMerged($plotInSouth)) {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $areaXMax = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMax = $plotPosition->getZ() + $worldSettings->getPlotSize();
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }
        }

        return $areas;
    }

    /**
     * @return Area[]
     * Returns all areas that are extending a plot's border areas.
     */
    private function calculatePlotBorderExtensionAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergedPlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $plot->getSide(Facing::NORTH);
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            $plotInWest = $plot->getSide(Facing::WEST);
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$plot->isMerged($plotInNorth)) {
                if (!$plot->isMerged($plotInWest)) {
                    $area = new Area(
                        $plotPosition->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPosition->getZ() - 1,
                        $plotPosition->getX() - 2,
                        $plotPosition->getZ() - 1
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$plot->isMerged($plotInEast)) {
                    $area = new Area(
                        $plotPosition->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPosition->getZ() - 1,
                        $plotPosition->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPosition->getZ() - 1
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$plot->isMerged($plotInSouth)) {
                if (!$plot->isMerged($plotInWest)) {
                    $area = new Area(
                        $plotPosition->getX() - ($worldSettings->getRoadSize() - 1),
                        $plotPosition->getZ() + $worldSettings->getPlotSize(),
                        $plotPosition->getX() - 2,
                        $plotPosition->getZ() + $worldSettings->getPlotSize()
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$plot->isMerged($plotInEast)) {
                    $area = new Area(
                        $plotPosition->getX() + ($worldSettings->getPlotSize() + 1),
                        $plotPosition->getZ() + $worldSettings->getPlotSize(),
                        $plotPosition->getX() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPosition->getZ() + $worldSettings->getPlotSize()
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$plot->isMerged($plotInWest)) {
                if (!$plot->isMerged($plotInNorth)) {
                    $area = new Area(
                        $plotPosition->getX() - 1,
                        $plotPosition->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPosition->getX() - 1,
                        $plotPosition->getZ() - 2
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$plot->isMerged($plotInSouth)) {
                    $area = new Area(
                        $plotPosition->getX() - 1,
                        $plotPosition->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPosition->getX() - 1,
                        $plotPosition->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$plot->isMerged($plotInEast)) {
                if (!$plot->isMerged($plotInNorth)) {
                    $area = new Area(
                        $plotPosition->getX() + $worldSettings->getPlotSize(),
                        $plotPosition->getZ() - ($worldSettings->getRoadSize() - 1),
                        $plotPosition->getX() + $worldSettings->getPlotSize(),
                        $plotPosition->getZ() - 2
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$plot->isMerged($plotInSouth)) {
                    $area = new Area(
                        $plotPosition->getX() + $worldSettings->getPlotSize(),
                        $plotPosition->getZ() + ($worldSettings->getPlotSize() + 1),
                        $plotPosition->getX() + $worldSettings->getPlotSize(),
                        $plotPosition->getZ() + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }
        }

        return $areas;
    }
}