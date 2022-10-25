<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\MergePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\tasks\utils\PlotAreaCalculationTrait;
use ColinHDev\CPlot\tasks\utils\PlotBorderAreaCalculationTrait;
use ColinHDev\CPlot\tasks\utils\RoadAreaCalculationTrait;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\ChunkLockId;
use pocketmine\world\World;
use function array_merge;
use function count;
use function spl_object_id;

/**
 * @phpstan-type PlotIdentifier string
 */
final class PlotLockManager {
    use SingletonTrait;
    use PlotAreaCalculationTrait;
    use PlotBorderAreaCalculationTrait;
    use RoadAreaCalculationTrait;

    /** @phpstan-var array<PlotIdentifier, PlotLockID[]>  */
    private array $plotLocks = [];

    /**
     * Returns whether any lock is currently holden on the {@see Plot} or any of its {@see MergePlot}s.
     * This should be checked to ensure that nothing changes a plot or its area while you want to modify it in an
     * async task.
     */
    public function isPlotLocked(Plot $plot) : bool {
        /** @phpstan-var PlotIdentifier $identifier */
        foreach (array_merge([$plot->toString() => $plot], $plot->getMergePlots()) as $identifier => $basePlot) {
            if (isset($this->plotLocks[$identifier]) && count($this->plotLocks[$identifier]) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Unlike {@see PlotLockManager::isPlotLocked()}, this will return true if any lock that is held on the {@see Plot}
     * or any of its {@see MergePlot}s is incompatible with the given {@see PlotLockID}.
     * This should be checked to ensure that nothing changes a plot or its area while you want to modify it in an
     * async task.
     */
    public function isPlotLockedForOperation(Plot $plot, PlotLockID $lockID) : bool {
        /** @phpstan-var PlotIdentifier $identifier */
        foreach (array_merge([$plot->toString() => $plot], $plot->getMergePlots()) as $identifier => $basePlot) {
            if (isset($this->plotLocks[$identifier]) && count($this->plotLocks[$identifier]) > 0) {
                foreach ($this->plotLocks[$identifier] as $plotLockID) {
                    if (!$plotLockID->isCompatible($lockID)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Flags a {@see Plot} as locked, usually for async modification.
     *
     * This is an **advisory lock**. This means that the lock does **not** prevent the chunk from being modified on the
     * main thread, such as by setBlock() or setBiomeId(). However, you can use it to detect when such modifications
     * have taken place - unlockChunk() with the same lockID will fail and return false if this happens.
     *
     * This is used internally during async modification, like plot merging, to ensure that no conflicting data is
     * created or the road is not correctly changed.
     *
     * WARNING: Be sure to release all locks once you're done with them.
     *
     * @throws \InvalidArgumentException if the {@see Plot} or any of its {@see MergePlot}s is already locked and its
     * locks are not compatible with the given one.
     */
    public function lockPlots(PlotLockID $lockID, Plot ...$plots) : void {
        $lockedChunks = [];
        foreach($plots as $plot) {
            try {
                $this->lockBasePlot($plot, $lockID);
                foreach ($plot->getMergePlots() as $mergePlot) {
                    $this->lockBasePlot($mergePlot, $lockID);
                }
            } catch (\InvalidArgumentException $exception) {
                $this->unlockPlots($lockID, ...$plots);
                throw $exception;
            }
            $chunkLockId = $lockID->getChunkLockId();
            if (!($chunkLockId instanceof ChunkLockId)) {
                continue;
            }
            $world = $plot->getWorld();
            if (!($world instanceof World)) {
                continue;
            }
            $chunks = [];
            $this->getChunksFromAreas("plot", $this->calculateBasePlotAreas($plot->getWorldSettings(), $plot), $chunks);
            $this->getChunksFromAreas("road", $this->calculateMergeRoadAreas($plot->getWorldSettings(), $plot), $chunks);
            $this->getChunksFromAreas("border", $this->calculatePlotBorderAreas($plot->getWorldSettings(), $plot), $chunks);
            try {
                foreach($chunks as $chunkHash => $chunkData) {
                    if (isset($lockedChunks[$chunkHash])) {
                        continue;
                    }
                    World::getXZ($chunkHash, $chunkX, $chunkZ);
                    $world->lockChunk($chunkX, $chunkZ, $chunkLockId);
                    $lockedChunks[$chunkHash] = true;
                }
            } catch(\InvalidArgumentException $exception) {
                $this->unlockPlots($lockID, ...$plots);
                throw $exception;
            }
        }
    }

    /**
     * Flags a {@see Plot} as locked, usually for async modification.
     *
     * This is an **advisory lock**. This means that the lock does **not** prevent the chunk from being modified on the
     * main thread, such as by setBlock() or setBiomeId(). However, you can use it to detect when such modifications
     * have taken place - unlockChunk() with the same lockID will fail and return false if this happens.
     *
     * This is used internally during async modification, like plot merging, to ensure that no conflicting data is
     * created or the road is not correctly changed.
     *
     * WARNING: Be sure to release all locks once you're done with them.
     *
     * Returns false if the {@see Plot} or any of its {@see MergePlot}s is already locked and its locks are not
     * compatible with the given one.
     */
    public function lockPlotsSilent(PlotLockID $lockID, Plot ...$plots) : bool {
        try {
            $this->lockPlots($lockID, ...$plots);
        } catch (\InvalidArgumentException) {
            return false;
        }
        return true;
    }

    /**
     * @throws \InvalidArgumentException if the {@see BasePlot} is already locked and its locks are not compatible with the given one.
     *@internal method which is used by {@see PlotLockManager::lockPlots()} to lock a simple {@see BasePlot} instance.
     */
    private function lockBasePlot(BasePlot $plot, PlotLockID $lockID) : void {
        if (isset($this->plotLocks[$plot->toString()])) {
            foreach ($this->plotLocks[$plot->toString()] as $plotLockID) {
                if (!$plotLockID->isCompatible($lockID)) {
                    throw new \InvalidArgumentException("Plot " . $plot->toString() . " is already locked.");
                }
            }
        } else {
            $this->plotLocks[$plot->toString()] = [];
        }
        $this->plotLocks[$plot->toString()][spl_object_id($lockID)] = $lockID;
    }

    /**
     * Unlocks a {@see Plot} and its {@see MergePlot}s who were previously locked by {@see PlotLockManager::lockPlots()}.
     *
     * You must provide the same lockID class instance as provided to lockPlot().
     * If a null lockID is given, any existing lock will be removed from the chunk, regardless of who owns it.
     *
     * Returns true if unlocking was successful, false otherwise.
     */
    public function unlockPlots(?PlotLockID $lockID, Plot ...$plots) : bool {
        $chunkLockId = $lockID?->getChunkLockId();
        $unlockedChunks = [];
        $success = true;
        foreach ($plots as $plot) {
            $success = $this->unlockBasePlot($plot, $lockID) && $success;
            foreach ($plot->getMergePlots() as $mergePlot) {
                $success = $this->unlockBasePlot($mergePlot, $lockID) && $success;
            }
            if ($lockID === null || $chunkLockId instanceof ChunkLockId) {
                $world = $plot->getWorld();
                if ($world instanceof World) {
                    $chunks = [];
                    $this->getChunksFromAreas("plot", $this->calculateBasePlotAreas($plot->getWorldSettings(), $plot), $chunks);
                    $this->getChunksFromAreas("road", $this->calculateMergeRoadAreas($plot->getWorldSettings(), $plot), $chunks);
                    $this->getChunksFromAreas("border", $this->calculatePlotBorderAreas($plot->getWorldSettings(), $plot), $chunks);
                    foreach($chunks as $chunkHash => $chunkData) {
                        if (isset($unlockedChunks[$chunkHash])) {
                            continue;
                        }
                        World::getXZ($chunkHash, $chunkX, $chunkZ);
                        if ($world->unlockChunk($chunkX, $chunkZ, $chunkLockId)) {
                            $unlockedChunks[$chunkHash] = true;
                        } else {
                            $success = false;
                        }
                    }
                }
            }
        }
        return $success;
    }

    /**
     * @internal method which is used by {@see PlotLockManager::unlockPlots()} to unlock a simple {@see BasePlot} instance.
     */
    private function unlockBasePlot(BasePlot $plot, ?PlotLockID $lockID) : bool {
        $plotIdentifier = $plot->toString();
        if (isset($this->plotLocks[$plotIdentifier])) {
            if ($lockID instanceof PlotLockID) {
                if (isset($this->plotLocks[$plotIdentifier][spl_object_id($lockID)])) {
                    unset($this->plotLocks[$plotIdentifier][spl_object_id($lockID)]);
                    if (count($this->plotLocks[$plotIdentifier]) === 0) {
                        unset($this->plotLocks[$plotIdentifier]);
                    }
                    return true;
                }
                return false;
            }
            unset($this->plotLocks[$plotIdentifier]);
            return true;
        }
        return false;
    }
}