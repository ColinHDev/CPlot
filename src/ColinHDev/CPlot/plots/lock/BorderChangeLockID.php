<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use pocketmine\world\ChunkLockId;

/**
 * This {@see PlotLockID} is used to lock a {@see Plot}, while changing its border.
 */
class BorderChangeLockID extends PlotLockID {

    private ChunkLockId $chunkLockId;

    public function isCompatible(PlotLockID $other) : bool {
        return $other instanceof AddPlotPlayerLockID || $other instanceof RemovePlotPlayerLockID;
    }

    public function getChunkLockId() : ?ChunkLockId {
        if (!isset($this->chunkLockId)) {
            $this->chunkLockId = new ChunkLockId();
        }
        return $this->chunkLockId;
    }
}