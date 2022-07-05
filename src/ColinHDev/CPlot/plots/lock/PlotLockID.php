<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use pocketmine\utils\NotCloneable;
use pocketmine\utils\NotSerializable;

/**
 * Represents a unique lock ID for use with plot locking.
 * @see PlotLockManager::lockPlot()
 * @see PlotLockManager::unlockPlot()
 */
final class PlotLockID {
    use NotCloneable;
    use NotSerializable;

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [];

    public function __construct(string $lockIdentifier) {

    }

    final public function isCompatible(PlotLockID $other) : bool {
        return isset(static::$compatibleLocks[$other::class]);
    }
}