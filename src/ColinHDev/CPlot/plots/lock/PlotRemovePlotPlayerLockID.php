<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while removing a {@see PlotPlayer} from it.
 */
class PlotRemovePlotPlayerLockID extends PlotLockID {

    public int $playerID;

    public function __construct(int $playerID) {
        $this->playerID = $playerID;
    }
}