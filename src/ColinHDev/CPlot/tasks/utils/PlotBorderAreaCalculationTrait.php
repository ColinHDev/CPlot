<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\math\Area;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\math\Facing;

trait PlotBorderAreaCalculationTrait {
    use AreaCalculationTrait;

    /**
     * @return Area[]
     * Returns all areas of a plot's border.
     */
    private function calculatePlotBorderAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots());
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getVector3NonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $plotPositionX = $plotPosition->getFloorX();
            $plotPositionZ = $plotPosition->getFloorZ();

            /** @var BasePlot $plotInNorth */
            $plotInNorth = $plot->getSide(Facing::NORTH);
            /** @var BasePlot $plotInSouth */
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            /** @var BasePlot $plotInWest */
            $plotInWest = $plot->getSide(Facing::WEST);
            /** @var BasePlot $plotInEast */
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$originPlot->isMerged($plotInNorth)) {
                if ($originPlot->isMerged($plotInWest)) {
                    $areaXMin = $plotPositionX - $worldSettings->getRoadSize();
                    $areaZMin = $plotPositionZ - 1;
                } else {
                    $areaXMin = $plotPositionX - 1;
                    $areaZMin = $plotPositionZ - 1;
                }
                if ($originPlot->isMerged($plotInEast)) {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $areaZMax = $plotPositionZ - 1;
                } else {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMax = $plotPositionZ - 1;
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$originPlot->isMerged($plotInSouth)) {
                if ($originPlot->isMerged($plotInWest)) {
                    $areaXMin = $plotPositionX - $worldSettings->getRoadSize();
                    $areaZMin = $plotPositionZ + $worldSettings->getPlotSize();
                } else {
                    $areaXMin = $plotPositionX - 1;
                    $areaZMin = $plotPositionZ + $worldSettings->getPlotSize();
                }
                if ($originPlot->isMerged($plotInEast)) {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize();
                } else {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize();
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$originPlot->isMerged($plotInWest)) {
                if ($originPlot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPositionX - 1;
                    $areaZMin = $plotPositionZ - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPositionX - 1;
                    $areaZMin = $plotPositionZ - 1;
                }
                if ($originPlot->isMerged($plotInSouth)) {
                    $areaXMax = $plotPositionX - 1;
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $areaXMax = $plotPositionX - 1;
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize();
                }
                $area = new Area($areaXMin, $areaZMin, $areaXMax, $areaZMax);
                $key = $area->toString();
                if (!isset($areas[$key])) {
                    $areas[$key] = $area;
                }
            }

            if (!$originPlot->isMerged($plotInEast)) {
                if ($originPlot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMin = $plotPositionZ - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMin = $plotPositionZ - 1;
                }
                if ($originPlot->isMerged($plotInSouth)) {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 1);
                } else {
                    $areaXMax = $plotPositionX + $worldSettings->getPlotSize();
                    $areaZMax = $plotPositionZ + $worldSettings->getPlotSize();
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

        $plots = array_merge([$originPlot], $originPlot->getMergePlots());
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getVector3NonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $plotPositionX = $plotPosition->getFloorX();
            $plotPositionZ = $plotPosition->getFloorZ();

            /** @var BasePlot $plotInNorth */
            $plotInNorth = $plot->getSide(Facing::NORTH);
            /** @var BasePlot $plotInSouth */
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            /** @var BasePlot $plotInWest */
            $plotInWest = $plot->getSide(Facing::WEST);
            /** @var BasePlot $plotInEast */
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$originPlot->isMerged($plotInNorth)) {
                if (!$originPlot->isMerged($plotInWest)) {
                    $area = new Area(
                        $plotPositionX - ($worldSettings->getRoadSize() - 1),
                        $plotPositionZ - 1,
                        $plotPositionX - 2,
                        $plotPositionZ - 1
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$originPlot->isMerged($plotInEast)) {
                    $area = new Area(
                        $plotPositionX + ($worldSettings->getPlotSize() + 1),
                        $plotPositionZ - 1,
                        $plotPositionX + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPositionZ - 1
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$originPlot->isMerged($plotInSouth)) {
                if (!$originPlot->isMerged($plotInWest)) {
                    $area = new Area(
                        $plotPositionX - ($worldSettings->getRoadSize() - 1),
                        $plotPositionZ + $worldSettings->getPlotSize(),
                        $plotPositionX - 2,
                        $plotPositionZ + $worldSettings->getPlotSize()
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$originPlot->isMerged($plotInEast)) {
                    $area = new Area(
                        $plotPositionX + ($worldSettings->getPlotSize() + 1),
                        $plotPositionZ + $worldSettings->getPlotSize(),
                        $plotPositionX + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                        $plotPositionZ + $worldSettings->getPlotSize()
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$originPlot->isMerged($plotInWest)) {
                if (!$originPlot->isMerged($plotInNorth)) {
                    $area = new Area(
                        $plotPositionX - 1,
                        $plotPositionZ - ($worldSettings->getRoadSize() - 1),
                        $plotPositionX - 1,
                        $plotPositionZ - 2
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$originPlot->isMerged($plotInSouth)) {
                    $area = new Area(
                        $plotPositionX - 1,
                        $plotPositionZ + ($worldSettings->getPlotSize() + 1),
                        $plotPositionX - 1,
                        $plotPositionZ + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
            }

            if (!$originPlot->isMerged($plotInEast)) {
                if (!$originPlot->isMerged($plotInNorth)) {
                    $area = new Area(
                        $plotPositionX + $worldSettings->getPlotSize(),
                        $plotPositionZ - ($worldSettings->getRoadSize() - 1),
                        $plotPositionX + $worldSettings->getPlotSize(),
                        $plotPositionZ - 2
                    );
                    $key = $area->toString();
                    if (!isset($areas[$key])) {
                        $areas[$key] = $area;
                    }
                }
                if (!$originPlot->isMerged($plotInSouth)) {
                    $area = new Area(
                        $plotPositionX + $worldSettings->getPlotSize(),
                        $plotPositionZ + ($worldSettings->getPlotSize() + 1),
                        $plotPositionX + $worldSettings->getPlotSize(),
                        $plotPositionZ + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
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

    /**
     * @return Area[]
     * Returns all areas of a plot's individual borders.
     */
    private function calculateIndividualPlotBorderAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots());
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getVector3NonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $plotPositionX = $plotPosition->getFloorX();
            $plotPositionZ = $plotPosition->getFloorZ();

            // Border in North
            $area = new Area(
                $plotPositionX - 1,
                $plotPositionZ - 1,
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ - 1
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in South
            $area = new Area(
                $plotPositionX - 1,
                $plotPositionZ + $worldSettings->getPlotSize(),
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in West
            $area = new Area(
                $plotPositionX - 1,
                $plotPositionZ - 1,
                $plotPositionX - 1,
                $plotPositionZ + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in East
            $area = new Area(
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ - 1,
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }
        }

        return $areas;
    }

    /**
     * @return Area[]
     * Returns all areas that are extending a plot's individual border areas.
     */
    private function calculateIndividualPlotBorderExtensionAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots());
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getVector3NonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());
            $plotPositionX = $plotPosition->getFloorX();
            $plotPositionZ = $plotPosition->getFloorZ();

            // Border in Northwest
            $area = new Area(
                $plotPositionX - ($worldSettings->getRoadSize() - 1),
                $plotPositionZ - 1,
                $plotPositionX - 2,
                $plotPositionZ - 1
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in Northeast
            $area = new Area(
                $plotPositionX + ($worldSettings->getPlotSize() + 1),
                $plotPositionZ - 1,
                $plotPositionX + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                $plotPositionZ - 1
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in Southwest
            $area = new Area(
                $plotPositionX - ($worldSettings->getRoadSize() - 1),
                $plotPositionZ + $worldSettings->getPlotSize(),
                $plotPositionX - 2,
                $plotPositionZ + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in Southeast
            $area = new Area(
                $plotPositionX + ($worldSettings->getPlotSize() + 1),
                $plotPositionZ + $worldSettings->getPlotSize(),
                $plotPositionX + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2)),
                $plotPositionZ + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in West-North
            $area = new Area(
                $plotPositionX - 1,
                $plotPositionZ - ($worldSettings->getRoadSize() - 1),
                $plotPositionX - 1,
                $plotPositionZ - 2
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in West-South
            $area = new Area(
                $plotPositionX - 1,
                $plotPositionZ + ($worldSettings->getPlotSize() + 1),
                $plotPositionX - 1,
                $plotPositionZ + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in East-North
            $area = new Area(
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ - ($worldSettings->getRoadSize() - 1),
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ - 2
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in East-South
            $area = new Area(
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ + ($worldSettings->getPlotSize() + 1),
                $plotPositionX + $worldSettings->getPlotSize(),
                $plotPositionZ + ($worldSettings->getPlotSize() + ($worldSettings->getRoadSize() - 2))
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }
        }

        return $areas;
    }
}