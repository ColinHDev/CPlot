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

    abstract public function isCompatible(PlotLockID $other) : bool;
}