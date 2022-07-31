<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while adding a {@see PlotPlayer} to it.
 */
class PlotAddPlotPlayerLockID extends PlotLockID {

    public int $playerID;

    public function __construct(int $playerID) {
        $this->playerID = $playerID;
    }


}