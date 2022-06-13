<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\lock;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\MergePlot;
use ColinHDev\CPlot\plots\Plot;
use pocketmine\utils\SingletonTrait;
use function spl_object_id;

/**
 * @phpstan-type PlotIdentifier string
 */
final class PlotLockManager {
    use SingletonTrait;

    /** @phpstan-var array<PlotIdentifier, PlotLockID[]>  */
    private array $plotLocks = [];

    /**
     * Returns whether a lock is currently holden on the {@see Plot} or any of its {@see MergePlot}s.
     * This should be checked to ensure that nothing changes a plot or its area while you want to modify it in an
     * async task.
     */
    public function isPlotLocked(Plot $plot) : bool {
        /** @phpstan-var PlotIdentifier $identifier */
        foreach (array_merge([$plot->toString() => $plot], $plot->getMergePlots()) as $identifier => $basePlot) {
            if (isset($this->plotLocks[$identifier])) {
                return true;
            }
        }
        return false;
    }

    public function lockPlotSilent(Plot $plot, PlotLockID $lockID) : bool {
        try {
            $this->lockPlot($plot, $lockID);
        } catch (\InvalidArgumentException) {
            return false;
        }
        return true;
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
     * @throws \InvalidArgumentException
     */
    public function lockPlot(Plot $plot, PlotLockID $lockID) : void {
        try {
            $this->lockBasePlot($plot, $lockID);
            foreach ($plot->getMergePlots() as $mergePlot) {
                $this->lockBasePlot($mergePlot, $lockID);
            }
        } catch (\InvalidArgumentException $exception) {
            $this->unlockPlot($plot, $lockID);
            throw $exception;
        }
    }

    /**
     * @throws \InvalidArgumentException
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

    public function unlockPlot(Plot $plot, ?PlotLockID $lockID) : bool {
        $success = $this->unlockBasePlot($plot, $lockID);
        foreach ($plot->getMergePlots() as $mergePlot) {
            $success = $this->unlockBasePlot($mergePlot, $lockID) && $success;
        }
        return $success;
    }

    private function unlockBasePlot(BasePlot $plot, ?PlotLockID $lockID) : bool {
        $plotIdentifier = $plot->toString();
        if (isset($this->plotLocks[$plotIdentifier]) && ($lockID === null || $this->plotLocks[$plotIdentifier] === $lockID)) {
            unset($this->plotLocks[$plotIdentifier]);
            return true;
        }
        return false;
    }
}