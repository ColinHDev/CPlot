<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while removing a trusted {@see Player} from the plot.
 */
class PlotRemoveTrustedLockID extends PlotLockID {

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [
        PlotClearLockID::class => true,
        PlotRemoveTrustedLockID::class => true
    ];
}