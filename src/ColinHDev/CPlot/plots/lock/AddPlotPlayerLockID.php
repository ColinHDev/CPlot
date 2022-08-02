<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while adding a {@see PlotPlayer} to it.
 */
class AddPlotPlayerLockID extends PlotLockID {

    public int $playerID;

    public function __construct(int $playerID) {
        $this->playerID = $playerID;
    }

    public function isCompatible(PlotLockID $other) : bool {
        if ($other instanceof ClearLockID) {
            return true;
        }
        if ($other instanceof self || $other instanceof RemovePlotPlayerLockID) {
            return $this->playerID !== $other->playerID;
        }
        return false;
    }
}