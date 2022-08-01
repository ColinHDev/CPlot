<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

class WallChangeLockID extends PlotLockID {

    public function isCompatible(PlotLockID $other) : bool {
        return $other instanceof AddPlotPlayerLockID || $other instanceof RemovePlotPlayerLockID;
    }
}