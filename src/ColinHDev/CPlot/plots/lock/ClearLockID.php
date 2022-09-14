<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while clearing its area.
 */
class ClearLockID extends PlotLockID {

    public function isCompatible(PlotLockID $other) : bool {
        return $other instanceof AddPlotPlayerLockID || $other instanceof RemovePlotPlayerLockID;
    }
}