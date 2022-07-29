<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while denying a {@see Player} from the plot.
 */
class PlotAddDeniedLockID extends PlotLockID {

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [
        PlotAddDeniedLockID::class => true,
        PlotClearLockID::class => true
    ];
}