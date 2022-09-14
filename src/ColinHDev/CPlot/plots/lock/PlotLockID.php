<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use pocketmine\utils\NotCloneable;
use pocketmine\utils\NotSerializable;

/**
 * Represents a unique lock ID for use with plot locking.
 * @see PlotLockManager::lockPlots()
 * @see PlotLockManager::unlockPlots()
 */
abstract class PlotLockID {
    use NotCloneable;
    use NotSerializable;

    /**
     * Checks if the given lock is compatible with this one.
     */
    abstract public function isCompatible(PlotLockID $other) : bool;
}