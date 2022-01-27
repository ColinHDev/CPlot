<?php

namespace ColinHDev\CPlot\tasks\utils;

use ColinHDev\CPlot\math\Area;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\math\Facing;

trait PlotBorderAreaCalculationTrait {

    /**
     * @return Area[]
     * Returns all areas of a plot's border.
     */
    private function calculatePlotBorderAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $plot->getSide(Facing::NORTH);
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            $plotInWest = $plot->getSide(Facing::WEST);
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$originPlot->isMerged($plotInNorth)) {
                if ($originPlot->isMerged($plotInWest)) {
                    $areaXMin = $plotPosition->getX() - $worldSettings->getRoadSize();
                    $areaZMin = $plotPosition->getZ() - 1;
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($originPlot->isMerged($plotInEast)) {
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

            if (!$originPlot->isMerged($plotInSouth)) {
                if ($originPlot->isMerged($plotInWest)) {
                    $areaXMin = $plotPosition->getX() - $worldSettings->getRoadSize();
                    $areaZMin = $plotPosition->getZ() + $worldSettings->getPlotSize();
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() + $worldSettings->getPlotSize();
                }
                if ($originPlot->isMerged($plotInEast)) {
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

            if (!$originPlot->isMerged($plotInWest)) {
                if ($originPlot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPosition->getX() - 1;
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($originPlot->isMerged($plotInSouth)) {
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

            if (!$originPlot->isMerged($plotInEast)) {
                if ($originPlot->isMerged($plotInNorth)) {
                    $areaXMin = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMin = $plotPosition->getZ() - $worldSettings->getRoadSize();
                } else {
                    $areaXMin = $plotPosition->getX() + $worldSettings->getPlotSize();
                    $areaZMin = $plotPosition->getZ() - 1;
                }
                if ($originPlot->isMerged($plotInSouth)) {
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

        $plots = array_merge([$originPlot], $originPlot->getMergePlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            $plotInNorth = $plot->getSide(Facing::NORTH);
            $plotInSouth = $plot->getSide(Facing::SOUTH);
            $plotInWest = $plot->getSide(Facing::WEST);
            $plotInEast = $plot->getSide(Facing::EAST);

            if (!$originPlot->isMerged($plotInNorth)) {
                if (!$originPlot->isMerged($plotInWest)) {
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
                if (!$originPlot->isMerged($plotInEast)) {
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

            if (!$originPlot->isMerged($plotInSouth)) {
                if (!$originPlot->isMerged($plotInWest)) {
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
                if (!$originPlot->isMerged($plotInEast)) {
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

            if (!$originPlot->isMerged($plotInWest)) {
                if (!$originPlot->isMerged($plotInNorth)) {
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
                if (!$originPlot->isMerged($plotInSouth)) {
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

            if (!$originPlot->isMerged($plotInEast)) {
                if (!$originPlot->isMerged($plotInNorth)) {
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
                if (!$originPlot->isMerged($plotInSouth)) {
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

    /**
     * @return Area[]
     * Returns all areas of a plot's individual borders.
     */
    private function calculateIndividualPlotBorderAreas(WorldSettings $worldSettings, Plot $originPlot) : array {
        /** @var Area[] $areas */
        $areas = [];

        $plots = array_merge([$originPlot], $originPlot->getMergePlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            // Border in North
            $area = new Area(
                $plotPosition->getX() - 1,
                $plotPosition->getZ() - 1,
                $plotPosition->getX() + $worldSettings->getPlotSize(),
                $plotPosition->getZ() - 1
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in South
            $area = new Area(
                $plotPosition->getX() - 1,
                $plotPosition->getZ() + $worldSettings->getPlotSize(),
                $plotPosition->getX() + $worldSettings->getPlotSize(),
                $plotPosition->getZ() + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in West
            $area = new Area(
                $plotPosition->getX() - 1,
                $plotPosition->getZ() - 1,
                $plotPosition->getX() - 1,
                $plotPosition->getZ() + $worldSettings->getPlotSize()
            );
            $key = $area->toString();
            if (!isset($areas[$key])) {
                $areas[$key] = $area;
            }

            // Border in East
            $area = new Area(
                $plotPosition->getX() + $worldSettings->getPlotSize(),
                $plotPosition->getZ() - 1,
                $plotPosition->getX() + $worldSettings->getPlotSize(),
                $plotPosition->getZ() + $worldSettings->getPlotSize()
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

        $plots = array_merge([$originPlot], $originPlot->getMergePlots() ?? []);
        /** @var BasePlot $plot */
        foreach ($plots as $plot) {
            $plotPosition = $plot->getPositionNonNull($worldSettings->getRoadSize(), $worldSettings->getPlotSize(), $worldSettings->getGroundSize());

            // Border in Northwest
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

            // Border in Northeast
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

            // Border in Southwest
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

            // Border in Southeast
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

            // Border in West-North
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

            // Border in West-South
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

            // Border in East-North
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

            // Border in East-South
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

        return $areas;
    }
}