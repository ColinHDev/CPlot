<?php

namespace ColinHDev\CPlotAPI;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\flags\FlagManager;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Ramsey\Uuid\Uuid;

class Plot extends BasePlot {

    private int $biomeID;
    private ?string $ownerUUID;
    private ?int $claimTime;
    private ?string $alias;

    /** @var null | MergePlot[] */
    private ?array $mergedPlots = null;

    /** @var null | PlotPlayer[] */
    private ?array $plotPlayers = null;

    /** @var null | BaseFlag[] */
    private ?array $flags = null;

    /** @var null | PlotRate[] */
    private ?array $plotRates = null;

    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $ownerUUID = null, ?int $claimTime = null, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->ownerUUID = $ownerUUID;
        $this->claimTime = $claimTime;
        $this->alias = $alias;
    }

    public function getBiomeID() : int {
        return $this->biomeID;
    }

    public function getOwnerUUID() : ?string {
        return $this->ownerUUID;
    }

    /**
     * @return int | null
     * returns int as the last played time in seconds
     * returns null if the result couldn't be found
     */
    public function getOwnerLastPlayed() : ?int {
        // the plot has no owner
        if ($this->ownerUUID === null) return null;

        // plot owner is online and therefore not inactive
        $owner = Server::getInstance()->getPlayerByRawUUID(Uuid::fromString($this->ownerUUID));
        if ($owner !== null) return (int) (microtime(true) * 1000);

        // check if the last time the player played should be fetched from the offline data file or the database
        switch (ResourceManager::getInstance()->getConfig()->get("lastPlayed.origin", "database")) {
            case "offline_data":
                // plot owner's name couldn't be fetched from the database
                // we return null so the plot doesn't get falsely stated as inactive because of a database error
                $ownerName = CPlot::getInstance()->getProvider()->getPlayerNameByUUID($this->ownerUUID);
                if ($ownerName === null) return null;

                // if plot owner isn't an instance of OfflinePlayer it is one of Player and therefore online on the server
                $owner = Server::getInstance()->getOfflinePlayer($ownerName);
                if (!$owner instanceof OfflinePlayer) return (int) (microtime(true) * 1000);

                // check if the plot owner's offline player data even exists
                // if not we try to fetch the last time the player played from the database
                // this could be null if the server admin deleted the plot owners offline player data file
                $lastPlayed = $owner->getLastPlayed();
                if ($lastPlayed !== null) break;

            default:
            case "database":
                // plot owner's data couldn't be fetched from the database
                // we return null so the plot doesn't get falsely stated as inactive because of a database error
                $owner = CPlot::getInstance()->getProvider()->getPlayerByUUID($this->ownerUUID);
                if ($owner === null) return null;

                $lastPlayed = $owner->getLastPlayed();
                break;
        }

        // the last played time is saved in milliseconds therefore we devide by 1000 and cast it to an integer
        // the float gets rounded up in favor of the plot owner
        return (int) ceil($lastPlayed / 1000);
    }

    public function setOwnerUUID(?string $ownerUUID) : void {
        $this->ownerUUID = $ownerUUID;
    }

    public function getClaimTime() : ?int {
        return $this->claimTime;
    }

    public function setClaimTime(?int $claimTime) : void {
        $this->claimTime = $claimTime;
    }

    public function getAlias() : ?string {
        return $this->alias;
    }


    public function loadMergedPlots() : bool {
        if ($this->mergedPlots !== null) return true;
        $this->mergedPlots = CPlot::getInstance()->getProvider()->getMergedPlots($this);
        if ($this->mergedPlots === null) return false;
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
        return true;
    }

    /**
     * @return MergePlot[] | null
     */
    public function getMergedPlots() : ?array {
        return $this->mergedPlots;
    }

    public function isMerged(BasePlot $plot) : bool {
        if ($this->isSame($plot, false)) return true;
        if ($this->mergedPlots === null) return false;
        return isset($this->mergedPlots[$plot->toString()]);
    }

    /**
     * @param MergePlot[] | null $mergedPlots
     */
    public function setMergedPlots(?array $mergedPlots) : void {
        $this->mergedPlots = $mergedPlots;
    }

    public function addMerge(MergePlot $mergedPlot) : bool {
        if ($this->mergedPlots === null) return false;
        $this->mergedPlots[$mergedPlot->toString()] = $mergedPlot;
        return true;
    }

    public function merge(self $plot) : bool {
        if ($this->mergedPlots === null && !$this->loadMergedPlots()) return false;
        if ($plot->getMergedPlots() === null && !$plot->loadMergedPlots()) return false;

        foreach (array_merge([$plot], $plot->getMergedPlots()) as $mergedPlot) {
            $this->addMerge(MergePlot::fromBasePlot($mergedPlot, $this->x, $this->z));
        }

        return CPlot::getInstance()->getProvider()->mergePlots($this, $plot, ...$plot->getMergedPlots());
    }


    public function loadPlotPlayers() : bool {
        if ($this->plotPlayers !== null) return true;
        $this->plotPlayers = CPlot::getInstance()->getProvider()->getPlotPlayers($this);
        if ($this->plotPlayers === null) return false;
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
        return true;
    }

    /**
     * @return PlotPlayer[] | null
     */
    public function getPlotPlayers() : ?array {
        return $this->plotPlayers;
    }

    public function getPlotPlayer(string $playerUUID) : ?PlotPlayer {
        if ($this->plotPlayers === null) return null;

        if (isset($this->plotPlayers[$playerUUID])) return $this->plotPlayers[$playerUUID];
        if (isset($this->plotPlayers["*"])) return $this->plotPlayers["*"];

        return null;
    }

    /**
     * @param PlotPlayer[] | null $plotPlayers
     */
    public function setPlotPlayers(?array $plotPlayers) : void {
        $this->plotPlayers = $plotPlayers;
    }

    public function addPlotPlayer(PlotPlayer $plotPlayer) : bool {
        if ($this->plotPlayers === null) return false;
        $this->plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
        return true;
    }

    public function removePlotPlayer(string $playerUUID) : bool {
        if ($this->plotPlayers === null) return false;
        unset($this->plotPlayers[$playerUUID]);
        return true;
    }


    public function loadFlags() : bool {
        if ($this->flags !== null) return true;
        $this->flags = CPlot::getInstance()->getProvider()->getPlotFlags($this);
        if ($this->flags === null) return false;
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
        return true;
    }

    /**
     * @return BaseFlag[] | null
     */
    public function getFlags() : ?array {
        return $this->flags;
    }

    public function getFlagByID(string $flagID) : ?BaseFlag {
        if ($this->flags !== null) {
            if (isset($this->flags[$flagID])) return $this->flags[$flagID];
        }
        return null;
    }

    /**
     * @param BaseFlag[] | null $flags
     */
    public function setFlags(?array $flags) : void {
        $this->flags = $flags;
    }

    public function addFlag(BaseFlag $flag) : bool {
        if ($this->flags === null) return false;
        $this->flags[$flag->getID()] = $flag;
        return true;
    }

    public function removeFlag(string $flagID) : bool {
        if ($this->flags === null) return false;
        unset($this->flags[$flagID]);
        return true;
    }


    public function loadPlotRates() : bool {
        if ($this->plotRates !== null) return true;
        $this->plotRates = CPlot::getInstance()->getProvider()->getPlotRates($this);
        if ($this->plotRates === null) return false;
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
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

    public function addPlotRate(PlotRate $plotRate) : bool {
        if ($this->plotRates === null) return false;
        $this->plotRates[$plotRate->toString()] = $plotRate;
        return true;
    }

    public function teleportTo(Player $player, bool $toPlotCenter = false) : bool {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->worldName);
        if ($world === null) return false;

        if ($this->loadFlags()) {
            $flag = $this->getFlagNonNullByID(FlagIDs::FLAG_SPAWN);
            $spawn = $flag?->getValueNonNull();
            if ($spawn instanceof Location) {
                return $player->teleport(
                    Location::fromObject($spawn, $world, $spawn->getYaw(), $spawn->getPitch())
                );
            }
        }

        if ($this->loadMergedPlots() && count($this->mergedPlots) >= 1) {
            $northestPlot = $this;
            foreach ($this->mergedPlots as $mergedPlot) {
                if ($northestPlot->getZ() <= $mergedPlot->getZ()) continue;
                $northestPlot = $mergedPlot;
            }
            $northestPlot->teleportTo($player, $toPlotCenter);
        }

        return parent::teleportTo($player, $toPlotCenter);
    }


    public function isSame(BasePlot $plot, bool $checkMerge = true) : bool {
        if ($checkMerge && !$plot instanceof Plot) {
            $plot = $plot->toPlot() ?? $plot;
        }
        return parent::isSame($plot);
    }

    public function isOnPlot(Position $position, bool $checkMerge = true) : bool {
        if (!$checkMerge || !$this->loadMergedPlots()) return parent::isOnPlot($position);
        foreach ($this->mergedPlots as $mergedPlot) {
            if ($mergedPlot->isOnPlot($position)) return true;
        }
        // TODO improve the following check, maybe @see EntityExplodeAsyncTask
        $plot = self::fromPosition($position);
        if ($plot !== null && $this->isSame($plot)) return true;
        return false;
    }


    public function toBasePlot() : BasePlot {
        return new BasePlot($this->worldName, $this->x, $this->z);
    }


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
        $basePlotInNorth = parent::fromPosition($position->getSide(Facing::NORTH, $worldSettings->getRoadSize()));
        $basePlotInSouth = parent::fromPosition($position->getSide(Facing::SOUTH, $worldSettings->getRoadSize()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            $plotInNorth = $basePlotInNorth->toPlot();
            if ($plotInNorth === null) return null;
            if ($plotInNorth->isSame($basePlotInSouth)) return $plotInNorth;
            return null;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = parent::fromPosition($position->getSide(Facing::WEST, $worldSettings->getRoadSize()));
        $basePlotInEast = parent::fromPosition($position->getSide(Facing::EAST, $worldSettings->getRoadSize()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            $plotInWest = $basePlotInWest->toPlot();
            if ($plotInWest === null) return null;
            if ($plotInWest->isSame($basePlotInEast)) return $plotInWest;
            return null;
        }

        // check for: position = road center
        $basePlotInNorthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInNorthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            $plotInNorthWest = $basePlotInNorthWest->toPlot();
            if ($plotInNorthWest === null) return null;
            if ($plotInNorthWest->isSame($basePlotInNorthEast) && $plotInNorthWest->isSame($basePlotInSouthWest) && $plotInNorthWest->isSame($basePlotInSouthEast)) return $plotInNorthWest;
            return null;
        }

        return null;
    }


    public function __serialize() : array {
        $data = parent::__serialize();

        $data["biomeID"] = $this->biomeID;
        $data["ownerUUID"] = $this->ownerUUID;
        $data["claimTime"] = $this->claimTime;
        $data["alias"] = $this->alias;

        $data["mergedPlots"] = serialize($this->mergedPlots);
        $data["plotPlayers"] = serialize($this->plotPlayers);
        $data["flags"] = serialize($this->flags);
        $data["plotRates"] = serialize($this->plotRates);
        return $data;
    }

    public function __unserialize(array $data) : void {
        parent::__unserialize($data);

        $this->biomeID = $data["biomeID"];
        $this->ownerUUID = $data["ownerUUID"];
        $this->claimTime = $data["claimTime"];
        $this->alias = $data["alias"];

        $this->mergedPlots = unserialize($data["mergedPlots"]);
        $this->plotPlayers = unserialize($data["plotPlayers"]);
        $this->flags = unserialize($data["flags"]);
        $this->plotRates = unserialize($data["plotRates"]);
    }
}