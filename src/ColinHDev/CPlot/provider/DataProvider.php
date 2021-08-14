<?php

namespace ColinHDev\CPlot\provider;

use ColinHDev\CPlotAPI\players\Player;
use ColinHDev\CPlotAPI\PlotPlayer;
use ColinHDev\CPlotAPI\PlotRate;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\Plot;

abstract class DataProvider {

    /** @var Cache[] */
    private array $caches;

    public function __construct() {
        $this->caches = [
            CacheIDs::CACHE_PLAYER => new Cache(64),
            CacheIDs::CACHE_WORLDSETTING => new Cache(16),
            CacheIDs::CACHE_PLOT => new Cache(128),
        ];
    }

    final public function getPlayerCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLAYER];
    }

    final public function getWorldSettingCache() : Cache {
        return $this->caches[CacheIDs::CACHE_WORLDSETTING];
    }

    final public function getPlotCache() : Cache {
        return $this->caches[CacheIDs::CACHE_PLOT];
    }

    abstract public function getPlayerByUUID(string $playerUUID) : ?Player;
    abstract public function getPlayerByName(string $playerName) : ?Player;
    /**
     * @deprecated due to @see Provider::getPlayerByUUID()
     */
    abstract public function getPlayerNameByUUID(string $playerUUID) : ?string;
    /**
     * @deprecated due to @see Provider::getPlayerByName()
     */
    abstract public function getPlayerUUIDByName(string $playerName) : ?string;
    abstract public function setPlayer(Player $player) : bool;

    abstract public function getWorld(string $worldName) : ?WorldSettings;
    abstract public function addWorld(string $worldName, WorldSettings $worldSettings) : bool;

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