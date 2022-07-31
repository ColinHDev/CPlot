<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while clearing its area.
 */
class ClearLockID extends PlotLockID {

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [
        PlotAddDeniedLockID::class => true,
        PlotAddHelperLockID::class => true,
        PlotAddTrustedLockID::class => true,
        PlotRemoveDeniedLockID::class => true,
        PlotRemoveHelperLockID::class => true,
        PlotRemoveTrustedLockID::class => true
    ];
}