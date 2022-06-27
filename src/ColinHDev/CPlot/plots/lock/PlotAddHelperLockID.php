<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while adding a {@see Player} as a helper to the plot.
 */
class PlotAddHelperLockID extends PlotLockID {

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [
        PlotAddHelperLockID::class => true,
        PlotClearLockID::class => true
    ];
}