<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\FlagManager;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\math\Facing;
use pocketmine\world\Position;

class Plot extends BasePlot {

    private int $biomeID;
    private ?string $ownerUUID;
    private ?int $claimTime;
    private ?string $alias;

    /** @var null | MergedPlot[] */
    private ?array $mergedPlots = null;

    /** @var null | PlotPlayer[] */
    private ?array $plotPlayers = null;

    /** @var null | BaseFlag[] */
    private ?array $flags = null;

    /** @var null | PlotRate[] */
    private ?array $plotRates = null;

    /**
     * Plot constructor.
     * @param string            $worldName
     * @param int               $x
     * @param int               $z
     * @param int               $biomeID
     * @param null | string     $ownerUUID
     * @param null | int        $claimTime
     * @param null | string     $alias
     */
    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $ownerUUID = null, ?int $claimTime = null, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->ownerUUID = $ownerUUID;
        $this->claimTime = $claimTime;
        $this->alias = $alias;
    }

    /**
     * @return int
     */
    public function getBiomeID() : int {
        return $this->biomeID;
    }

    /**
     * @return string | null
     */
    public function getOwnerUUID() : ?string {
        return $this->ownerUUID;
    }

    /**
     * @param string | null $ownerUUID
     */
    public function setOwnerUUID(?string $ownerUUID) : void {
        $this->ownerUUID = $ownerUUID;
    }

    /**
     * @return int | null
     */
    public function getClaimTime() : ?int {
        return $this->claimTime;
    }

    /**
     * @param int | null $claimTime
     */
    public function setClaimTime(?int $claimTime) : void {
        $this->claimTime = $claimTime;
    }

    /**
     * @return string | null
     */
    public function getAlias() : ?string {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function loadMergedPlots() : bool {
        if ($this->mergedPlots !== null) return true;
        $this->mergedPlots = CPlot::getInstance()->getProvider()->getMergedPlots($this);
        if ($this->mergedPlots === null) return false;
        CPlot::getInstance()->getProvider()->cachePlot($this);
        return true;
    }

    /**
     * @return MergedPlot[] | null
     */
    public function getMergedPlots() : ?array {
        return $this->mergedPlots;
    }

    /**
     * @param BasePlot $plot
     * @return bool
     */
    public function isMerged(BasePlot $plot) : bool {
        if ($this->isSame($plot, false)) return true;
        if ($this->mergedPlots === null) return false;
        return isset($this->mergedPlots[$plot->toString()]);
    }

    /**
     * @param MergedPlot[] | null $mergedPlots
     */
    public function setMergedPlots(?array $mergedPlots) : void {
        $this->mergedPlots = $mergedPlots;
    }

    /**
     * @param MergedPlot $mergedPlot
     * @return bool
     */
    public function addMerge(MergedPlot $mergedPlot) : bool {
        if ($this->mergedPlots === null) return false;
        $this->mergedPlots[$mergedPlot->toString()] = $mergedPlot;
        return true;
    }

    /**
     * @param Plot $plot
     * @return bool
     */
    public function merge(self $plot) : bool {
        if ($this->mergedPlots === null && !$this->loadMergedPlots()) return false;
        if ($plot->getMergedPlots() === null && !$plot->loadMergedPlots()) return false;

        if (count($plot->getMergedPlots()) > 0) {
            if (!CPlot::getInstance()->getProvider()->deleteMergedPlots($plot)) return false;
        }

        foreach (array_merge([$plot], $plot->getMergedPlots()) as $mergedPlot) {
            $this->addMerge(MergedPlot::fromBasePlot($mergedPlot, $this->x, $this->z));
        }

        return CPlot::getInstance()->getProvider()->mergePlots($this, $plot, ...$plot->getMergedPlots());
    }


    /**
     * @return bool
     */
    public function loadPlotPlayers() : bool {
        if ($this->plotPlayers !== null) return true;
        $this->plotPlayers = CPlot::getInstance()->getProvider()->getPlotPlayers($this);
        if ($this->plotPlayers === null) return false;
        CPlot::getInstance()->getProvider()->cachePlot($this);
        return true;
    }

    /**
     * @return PlotPlayer[] | null
     */
    public function getPlotPlayers() : ?array {
        return $this->plotPlayers;
    }

    /**
     * @param string $playerUUID
     * @return BaseFlag | null
     */
    public function getPlotPlayer(string $playerUUID) : ?PlotPlayer {
        if ($this->plotPlayers !== null) return null;
        if (!isset($this->plotPlayers[$playerUUID])) return null;
        return $this->plotPlayers[$playerUUID];
    }

    /**
     * @param PlotPlayer[] | null $plotPlayers
     */
    public function setPlotPlayers(?array $plotPlayers) : void {
        $this->plotPlayers = $plotPlayers;
    }

    /**
     * @param PlotPlayer $plotPlayer
     * @return bool
     */
    public function addPlotPlayer(PlotPlayer $plotPlayer) : bool {
        if ($this->plotPlayers === null) return false;
        $this->plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
        return true;
    }

    /**
     * @param string $playerUUID
     * @return bool
     */
    public function removePlotPlayer(string $playerUUID) : bool {
        if ($this->plotPlayers === null) return false;
        unset($this->plotPlayers[$playerUUID]);
        return true;
    }


    /**
     * @return bool
     */
    public function loadFlags() : bool {
        if ($this->flags !== null) return true;
        $this->flags = CPlot::getInstance()->getProvider()->getPlotFlags($this);
        if ($this->flags === null) return false;
        CPlot::getInstance()->getProvider()->cachePlot($this);
        return true;
    }

    /**
     * @return BaseFlag[] | null
     */
    public function getFlags() : ?array {
        return $this->flags;
    }

    /**
     * @param string $flagID
     * @return BaseFlag | null
     */
    public function getFlagByID(string $flagID) : ?BaseFlag {
        if ($this->flags !== null) {
            if (isset($this->flags[$flagID])) return $this->flags[$flagID];
        }
        return FlagManager::getInstance()->getFlagByID($flagID);
    }

    /**
     * @param BaseFlag[] | null $flags
     */
    public function setFlags(?array $flags) : void {
        $this->flags = $flags;
    }

    /**
     * @param BaseFlag $flag
     * @return bool
     */
    public function addFlag(BaseFlag $flag) : bool {
        if ($this->flags === null) return false;
        $this->flags[$flag->getID()] = $flag;
        return true;
    }

    /**
     * @param string $flagID
     * @return bool
     */
    public function removeFlag(string $flagID) : bool {
        if ($this->flags === null) return false;
        unset($this->flags[$flagID]);
        return true;
    }


    /**
     * @return bool
     */
    public function loadPlotRates() : bool {
        if ($this->plotRates !== null) return true;
        $this->plotRates = CPlot::getInstance()->getProvider()->getPlotRates($this);
        if ($this->plotRates === null) return false;
        CPlot::getInstance()->getProvider()->cachePlot($this);
        return true;
    }

    /**
     * @return PlotRate[] | null
     */
    public function getPlotRates() : ?array {
        return $this->plotRates;
    }

    /**
     * @param PlotRate[] | null $plotRates
     */
    public function setPlotRates(?array $plotRates) : void {
        $this->plotRates = $plotRates;
    }

    /**
     * @param PlotRate $plotRate
     * @return bool
     */
    public function addPlotRate(PlotRate $plotRate) : bool {
        if ($this->plotRates === null) return false;
        $this->plotRates[$plotRate->toString()] = $plotRate;
        return true;
    }


    public function isSame(BasePlot $plot, bool $checkMerge = true) : bool {
        if ($checkMerge && !$plot instanceof Plot) {
            $plot = $plot->toPlot() ?? $plot;
        }
        return parent::isSame($plot);
    }

    /**
     * @param Position  $position
     * @param bool      $checkMerge
     * @return self | null
     */
    public static function fromPosition(Position $position, bool $checkMerge = true) : ?self {
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName());
        if ($worldSettings === null) return null;

        // check for: position = plot
        $basePlot = parent::fromPosition($position);
        if ($basePlot !== null) {
            return $basePlot->toPlot();
        }

        if (!$checkMerge) return null;

        // check for: position = road between plots in north (-z) and south (+z)
        $basePlotInNorth = parent::fromPosition($position->getSide(Facing::NORTH, $worldSettings->getSizeRoad()));
        $basePlotInSouth = parent::fromPosition($position->getSide(Facing::SOUTH, $worldSettings->getSizeRoad()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            $plotInNorth = $basePlotInNorth->toPlot();
            if ($plotInNorth === null) return null;
            if ($plotInNorth->isSame($basePlotInSouth)) return $plotInNorth;
            return null;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = parent::fromPosition($position->getSide(Facing::WEST, $worldSettings->getSizeRoad()));
        $basePlotInEast = parent::fromPosition($position->getSide(Facing::EAST, $worldSettings->getSizeRoad()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            $plotInWest = $basePlotInWest->toPlot();
            if ($plotInWest === null) return null;
            if ($plotInWest->isSame($basePlotInEast)) return $plotInWest;
            return null;
        }

        // check for: position = road center
        $basePlotInNorthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getSizeRoad(), 0, - $worldSettings->getSizeRoad()), $position->getWorld()));
        $basePlotInNorthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getSizeRoad(), 0, - $worldSettings->getSizeRoad()), $position->getWorld()));
        $basePlotInSouthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getSizeRoad(), 0, $worldSettings->getSizeRoad()), $position->getWorld()));
        $basePlotInSouthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getSizeRoad(), 0, $worldSettings->getSizeRoad()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            $plotInNorthWest = $basePlotInNorthWest->toPlot();
            if ($plotInNorthWest === null) return null;
            if ($plotInNorthWest->isSame($basePlotInNorthEast) && $plotInNorthWest->isSame($basePlotInSouthWest) && $plotInNorthWest->isSame($basePlotInSouthEast)) return $plotInNorthWest;
            return null;
        }

        return null;
    }

    /**
     * @return array
     */
    public function __serialize() : array {
        return [
            "worldName" => $this->worldName, "x" => $this->x, "z" => $this->z,
            "biomeID" => $this->biomeID, "ownerUUID" => $this->ownerUUID, "claimTime" => $this->claimTime, "alias" => $this->alias,
            "mergedPlots" => serialize($this->mergedPlots), "plotPlayers" => serialize($this->plotPlayers), "flags" => serialize($this->flags)
        ];
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data) : void {
        $this->worldName = $data["worldName"];
        $this->x = $data["x"];
        $this->z = $data["z"];

        $this->biomeID = $data["biomeID"];
        $this->ownerUUID = $data["ownerUUID"];
        $this->claimTime = $data["claimTime"];
        $this->alias = $data["alias"];

        $this->mergedPlots = unserialize($data["mergedPlots"]);
        $this->plotPlayers = unserialize($data["plotPlayers"]);
        $this->flags = unserialize($data["flags"]);
    }
}