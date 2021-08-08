<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlotAPI\PlotPlayer;
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

    abstract public function getPlayerNameByUUID(string $playerUUID) : ?string;
    abstract public function getPlayerUUIDByName(string $playerName) : ?string;
    abstract public function setPlayer(string $playerUUID, string $playerName) : bool;

    abstract public function getWorld(string $worldName) : ?WorldSettings;
    abstract public function addWorld(string $worldName, WorldSettings $settings) : bool;

    abstract public function getPlot(string $worldName, int $x, int $z) : ?Plot;
    abstract public function getPlotsByOwnerUUID(string $ownerUUID) : ?array;
    abstract public function getPlotByAlias(string $alias) : ?Plot;
    abstract public function savePlot(Plot $plot) : bool;
    abstract public function deletePlot(string $worldName, int $x, int $z) : bool;

    abstract public function getMergedPlots(Plot $plot) : ?array;
    abstract public function getMergeOrigin(BasePlot $plot) : ?Plot;
    abstract public function mergePlots(Plot $origin, BasePlot ...$plots) : bool;
    abstract public function deleteMergedPlots(Plot $plot) : bool;

    abstract public function getPlotPlayers(Plot $plot) : ?array;
    abstract public function savePlotPlayer(Plot $plot, PlotPlayer $plotPlayer) : bool;
    abstract public function deletePlotPlayer(Plot $plot, string $playerUUID) : bool;

    abstract public function getPlotFlags(Plot $plot) : ?array;
    abstract public function savePlotFlag(Plot $plot, BaseFlag $flag) : bool;
    abstract public function deletePlotFlag(Plot $plot, string $flagID) : bool;
    abstract public function deletePlotFlags(Plot $plot) : bool;

    abstract public function close() : bool;

    /**
     * @param string $worldName
     * @return WorldSettings | null
     */
    final protected function getWorldFromCache(string $worldName) : ?WorldSettings {
        if ($this->worldCacheSize <= 0) return null;
        if (!isset($this->worldCache[$worldName])) return null;
        return $this->worldCache[$worldName];
    }

    /**
     * @param string $worldName
     * @param WorldSettings $settings
     */
    final protected function cacheWorld(string $worldName, WorldSettings $settings) : void {
        if ($this->worldCacheSize <= 0) return;
        if (isset($this->worldCache[$worldName])) {
            unset($this->worldCache[$worldName]);
        } else if ($this->worldCacheSize <= count($this->worldCache)) {
            array_shift($this->worldCache);
        }
        $this->worldCache = array_merge([$worldName => clone $settings], $this->worldCache);
    }

    /**
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     * @return BasePlot | null
     */
    final protected function getPlotFromCache(string $worldName, int $x, int $z) : ?BasePlot {
        if ($this->plotCacheSize <= 0) return null;
        $key = $worldName . ";" . $x . ";" . $z;
        if (!isset($this->plotCache[$key])) return null;
        return $this->plotCache[$key];
    }

    /**
     * @param BasePlot $plot
     */
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

    /**
     * @param string    $worldName
     * @param int       $x
     * @param int       $z
     */
    final protected function removePlotFromCache(string $worldName, int $x, int $z) : void {
        if ($this->plotCacheSize <= 0) return;
        $key = $worldName . ";" . $x . ";" . $z;
        if (!isset($this->plotCache[$key])) return;
        unset($this->plotCache[$key]);
    }
}