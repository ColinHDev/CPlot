<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlotAPI\MergedPlot;
use ColinHDev\CPlotAPI\PlotPlayer;
use ColinHDev\CPlotAPI\PlotRate;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\Plot;

abstract class DataProvider {

    /** @var WorldSettings[] */
    private array $worldCache = [];
    private int $worldCacheSize = 16;

    /** @var BasePlot[] */
    private array $plotCache = [];
    private int $plotCacheSize = 128;


    final protected function getWorldFromCache(string $worldName) : ?WorldSettings {
        if ($this->worldCacheSize <= 0) return null;
        if (!isset($this->worldCache[$worldName])) return null;
        return $this->worldCache[$worldName];
    }

    final protected function cacheWorld(string $worldName, WorldSettings $settings) : void {
        if ($this->worldCacheSize <= 0) return;
        if (isset($this->worldCache[$worldName])) {
            unset($this->worldCache[$worldName]);
        } else if ($this->worldCacheSize <= count($this->worldCache)) {
            array_shift($this->worldCache);
        }
        $this->worldCache = array_merge([$worldName => clone $settings], $this->worldCache);
    }


    final protected function getPlotFromCache(string $worldName, int $x, int $z) : ?BasePlot {
        if ($this->plotCacheSize <= 0) return null;
        $key = $worldName . ";" . $x . ";" . $z;
        if (!isset($this->plotCache[$key])) return null;
        return $this->plotCache[$key];
    }

    final public function cachePlot(BasePlot $plot) : void {
        if ($this->plotCacheSize <= 0) return;
        $key = $plot->toString();
        if (isset($this->plotCache[$key])) {
            unset($this->plotCache[$key]);
        } else if ($this->plotCache <= count($this->plotCache)) {
            array_shift($this->plotCache);
        }
        $this->plotCache = array_merge([$key => clone $plot], $this->plotCache);
    }

    final protected function removePlotFromCache(BasePlot $plot) : void {
        if ($this->plotCacheSize <= 0) return;
        // no old merged plots should be saved in the provider
        if ($plot instanceof Plot) {
            if ($plot->getMergedPlots() === null) {
                foreach ($plot->getMergedPlots() as $mergedPlot) {
                    $this->removePlotFromCache($mergedPlot);
                }
            } else {
                foreach ($this->plotCache as $possiblyMergedPlot) {
                    if (!$possiblyMergedPlot instanceof MergedPlot) continue;
                    if ($plot->getWorldName() !== $possiblyMergedPlot->getWorldName()) continue;
                    if ($plot->getX() !== $possiblyMergedPlot->getOriginX()) continue;
                    if ($plot->getZ() !== $possiblyMergedPlot->getOriginZ()) continue;
                    $this->removePlotFromCache($possiblyMergedPlot);
                }
            }
        }
        // remove plot
        $key = $plot->toString();
        if (!isset($this->plotCache[$key])) return;
        unset($this->plotCache[$key]);
    }


    abstract public function getPlayerNameByUUID(string $playerUUID) : ?string;
    abstract public function getPlayerUUIDByName(string $playerName) : ?string;
    abstract public function setPlayer(string $playerUUID, string $playerName) : bool;

    abstract public function getWorld(string $worldName) : ?WorldSettings;
    abstract public function addWorld(string $worldName, WorldSettings $settings) : bool;

    abstract public function getPlot(string $worldName, int $x, int $z) : ?Plot;
    abstract public function getPlotsByOwnerUUID(string $ownerUUID) : ?array;
    abstract public function getPlotByAlias(string $alias) : ?Plot;
    abstract public function savePlot(Plot $plot) : bool;
    abstract public function deletePlot(Plot $plot) : bool;

    abstract public function getMergedPlots(Plot $plot) : ?array;
    abstract public function getMergeOrigin(BasePlot $plot) : ?Plot;
    abstract public function mergePlots(Plot $origin, BasePlot ...$plots) : bool;

    abstract public function getNextFreePlot(string $worldName, int $limitXZ = 0) : ?Plot;

    abstract public function getPlotPlayers(Plot $plot) : ?array;
    abstract public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : bool;
    abstract public function deletePlotPlayer(Plot $plot, string $playerUUID) : bool;

    abstract public function getPlotFlags(Plot $plot) : ?array;
    abstract public function savePlotFlag(Plot $plot, BaseFlag $flag) : bool;
    abstract public function deletePlotFlag(Plot $plot, string $flagID) : bool;

    abstract public function getPlotRates(Plot $plot) : ?array;
    abstract public function savePlotRate(Plot $plot, PlotRate $plotRate) : bool;

    abstract public function close() : bool;


    /**
     * @return int[] | null
     * code from @see https://github.com/jasonwynn10/MyPlot
     */
    protected function findEmptyPlotSquared(int $a, int $b, array $plots) : ?array {
        if(!isset($plots[$a][$b]))
            return [$a, $b];
        if(!isset($plots[$b][$a]))
            return [$b, $a];
        if($a !== 0) {
            if(!isset($plots[-$a][$b]))
                return [-$a, $b];
            if(!isset($plots[$b][-$a]))
                return [$b, -$a];
        }
        if($b !== 0) {
            if(!isset($plots[-$b][$a]))
                return [-$b, $a];
            if(!isset($plots[$a][-$b]))
                return [$a, -$b];
        }
        if(($a | $b) === 0) {
            if(!isset($plots[-$a][-$b]))
                return [-$a, -$b];
            if(!isset($plots[-$b][-$a]))
                return [-$b, -$a];
        }
        return null;
    }
}