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
abstract class PlotLockID {
    use NotCloneable;
    use NotSerializable;

    /** @phpstan-var array array<class-string<PlotLockID>, true> */
    protected static array $compatibleLocks = [];

    final public function isCompatible(PlotLockID $other) : bool {
        return isset(self::$compatibleLocks[$other::class]);
    }
}