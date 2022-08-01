<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use pocketmine\utils\NotCloneable;
use pocketmine\utils\NotSerializable;
use pocketmine\world\ChunkLockId;

/**
 * Represents a unique lock ID for use with plot locking.
 * @see PlotLockManager::lockPlot()
 * @see PlotLockManager::unlockPlot()
 */
abstract class PlotLockID {
    use NotCloneable;
    use NotSerializable;

    /**
     * Checks if the given lock is compatible with this one.
     */
    abstract public function isCompatible(PlotLockID $other) : bool;

    /**
     * This should return a {@see ChunkLockId} if the chunks of the plot should be locked as well, while it is locked.
     * The chunks get automatically locked and unlocked when calling
     * {@see PlotLockManager::lockPlot()} and {@see PlotLockManager::unlockPlot()}.
     */
    public function getChunkLockId() : ?ChunkLockId {
        return null;
    }
}