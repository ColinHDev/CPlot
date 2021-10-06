<?php

namespace ColinHDev\CPlotAPI\plots;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\plots\flags\BaseFlag;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
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
    private ?array $mergePlots = null;

    /** @var null | PlotPlayer[] */
    private ?array $plotPlayers = null;

    /** @var null | BaseFlag[] */
    private ?array $flags = null;

    /** @var null | PlotRate[] */
    private ?array $plotRates = null;

    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->alias = $alias;
    }

    public function getBiomeID() : int {
        return $this->biomeID;
    }

    public function getOwnerUUID() : ?string {
        return $this->ownerUUID;
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


    public function loadMergePlots() : bool {
        if ($this->mergePlots !== null) return true;
        $this->mergePlots = CPlot::getInstance()->getProvider()->getMergePlots($this);
        if ($this->mergePlots === null) return false;
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
        return true;
    }

    /**
     * @return MergePlot[] | null
     */
    public function getMergePlots() : ?array {
        return $this->mergePlots;
    }

    public function isMerged(BasePlot $plot) : bool {
        if ($this->isSame($plot, false)) return true;
        if ($this->mergePlots === null) return false;
        return isset($this->mergePlots[$plot->toString()]);
    }

    public function addMergePlot(MergePlot $mergedPlot) : bool {
        if ($this->mergePlots === null) return false;
        $this->mergePlots[$mergedPlot->toString()] = $mergedPlot;
        return true;
    }

    public function merge(self $plot) : bool {
        if ($this->mergePlots === null && !$this->loadMergePlots()) return false;
        if ($plot->getMergePlots() === null && !$plot->loadMergePlots()) return false;

        foreach (array_merge([$plot], $plot->getMergePlots()) as $mergePlot) {
            $this->addMergePlot(MergePlot::fromBasePlot($mergePlot, $this->x, $this->z));
        }

        return CPlot::getInstance()->getProvider()->mergePlots($this, $plot, ...$plot->getMergePlots());
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
        $this->plotPlayers[$plotPlayer->getPlayerData()->getPlayerUUID()] = $plotPlayer;
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

    public function getFlagNonNullByID(string $flagID) : ?BaseFlag {
        $flag = $this->getFlagByID($flagID);
        if ($flag === null && $this->flags !== null) {
            $flag = FlagManager::getInstance()->getFlagByID($flagID);
        }
        return $flag;
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

        if (!$toPlotCenter) {
            if ($this->loadFlags()) {
                $flag = $this->getFlagNonNullByID(FlagIDs::FLAG_SPAWN);
                $relativeSpawnLocation = $flag?->getValue();
                if ($relativeSpawnLocation instanceof Location) {
                    return $player->teleport(
                        Location::fromObject(
                            $relativeSpawnLocation->addVector($this->getPosition()),
                            $world,
                            $relativeSpawnLocation->getYaw(),
                            $relativeSpawnLocation->getPitch()
                        )
                    );
                }
            }
        }

        if ($this->loadMergePlots() && count($this->mergePlots) >= 1) {
            $northestPlot = $this;
            foreach ($this->mergePlots as $mergedPlot) {
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
        if (!$checkMerge || !$this->loadMergePlots()) return parent::isOnPlot($position);
        foreach ($this->mergePlots as $mergedPlot) {
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

        $data["mergePlots"] = serialize($this->mergePlots);
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

        $this->mergePlots = unserialize($data["mergePlots"]);
        $this->plotPlayers = unserialize($data["plotPlayers"]);
        $this->flags = unserialize($data["flags"]);
        $this->plotRates = unserialize($data["plotRates"]);
    }
}