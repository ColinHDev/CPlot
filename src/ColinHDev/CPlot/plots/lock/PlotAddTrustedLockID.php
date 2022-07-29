<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while adding a {@see Player} as trusted to the plot.
 */
class PlotAddTrustedLockID extends PlotLockID {

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [
        PlotAddTrustedLockID::class => true,
        PlotClearLockID::class => true
    ];
}