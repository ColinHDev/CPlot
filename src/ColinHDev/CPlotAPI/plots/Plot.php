<?php

namespace ColinHDev\CPlotAPI\plots;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Plot extends BasePlot {

    private int $biomeID;
    private ?string $alias;

    /** @var null | array<string, MergePlot> */
    private ?array $mergePlots = null;
    /** @var null | array<string, PlotPlayer> */
    private ?array $plotPlayers = null;
    /** @var null | array<string, BaseAttribute> */
    private ?array $flags = null;
    /** @var null | array<string, PlotRate> */
    private ?array $plotRates = null;

    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $alias = null) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->alias = $alias;
    }

    public function getBiomeID() : int {
        return $this->biomeID;
    }

    public function getAlias() : ?string {
        return $this->alias;
    }

    /**
     * @return array<string, MergePlot>
     * @throws PlotException
     */
    public function getMergePlots() : array {
        $this->loadMergePlots();
        return $this->mergePlots;
    }

    /**
     * @throws PlotException
     */
    public function isMerged(BasePlot $plot) : bool {
        if ($this->isSame($plot, false)) {
            return true;
        }
        $this->loadMergePlots();
        return isset($this->mergePlots[$plot->toString()]);
    }

    /**
     * @throws PlotException
     */
    public function addMergePlot(MergePlot $mergedPlot) : void {
        $this->loadMergePlots();
        $this->mergePlots[$mergedPlot->toString()] = $mergedPlot;
    }

    /**
     * @throws PlotException
     */
    public function merge(self $plot) : void {
        foreach (array_merge([$plot], $plot->getMergePlots()) as $mergePlot) {
            $mergePlot = MergePlot::fromBasePlot($mergePlot->toBasePlot(), $this->x, $this->z);
            if (!CPlot::getInstance()->getProvider()->addMergePlot($this, $mergePlot)) {
                throw new PlotException($this, "Couldn't merge plot " . $mergePlot->toString() . " into " . $this->toString() . ".");
            }
            $this->addMergePlot($mergePlot);
        }

        foreach ($plot->getPlotPlayers() as $mergePlotPlayer) {
            if (!CPlot::getInstance()->getProvider()->savePlotPlayer($this, $mergePlotPlayer)) {
                throw new PlotException($this, "Couldn't merge plot player " . $mergePlotPlayer->getPlayerUUID() . " from plot " . $plot->toString() . " into " . $this->toString() . ".");
            }
            $this->addPlotPlayer($mergePlotPlayer);
        }

        foreach ($plot->getFlags() as $mergeFlag) {
            $flag = $this->getFlagByID($mergeFlag->getID());
            if ($flag === null) {
                $flag = $mergeFlag;
            } else {
                $flag = $flag->merge($mergeFlag->getValue());
            }
            if (!CPlot::getInstance()->getProvider()->savePlotFlag($this, $flag)) {
                throw new PlotException($this, "Couldn't merge plot flag " . $flag->getID() . " from plot " . $plot->toString() . " into " . $this->toString() . ".");
            }
            $this->addFlag($flag);
        }

        foreach ($plot->getPlotRates() as $mergePlotRate) {
            if (!CPlot::getInstance()->getProvider()->savePlotRate($this, $mergePlotRate)) {
                throw new PlotException($this, "Couldn't merge plot rate " . $mergePlotRate->toString() . " from plot " . $plot->toString() . " into " . $this->toString() . ".");
            }
            $this->addPlotRate($mergePlotRate);
        }
    }

    /**
     * @internal
     * @throws PlotException
     */
    public function loadMergePlots() : void {
        if ($this->mergePlots !== null) {
            return;
        }
        $this->mergePlots = CPlot::getInstance()->getProvider()->getMergePlots($this);
        if ($this->mergePlots === null) {
            throw new PlotException($this,"Couldn't load merge plots of plot " . $this->toString() . " from provider.");
        }
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotPlayers() : array {
        $this->loadPlotPlayers();
        return $this->plotPlayers;
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotPlayersByState(string $state) : array {
        $plotPlayers = [];
        foreach ($this->getPlotPlayers() as $plotPlayer) {
            if ($plotPlayer->getState() === $state) {
                $plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
            }
        }
        return $plotPlayers;
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotOwners() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_OWNER);
    }

    /**
     * @throws PlotException
     */
    public function hasPlotOwner() : bool {
        return count($this->getPlotOwners()) !== 0;
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotTrusted() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_TRUSTED);
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotHelpers() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_HELPER);
    }

    /**
     * @return array<string, PlotPlayer>
     * @throws PlotException
     */
    public function getPlotDenied() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_DENIED);
    }

    /**
     * @throws PlotException
     */
    public function getPlotPlayerExact(string $playerUUID) : ?PlotPlayer {
        $this->loadPlotPlayers();
        if (isset($this->plotPlayers[$playerUUID])) {
            return $this->plotPlayers[$playerUUID];
        }
        return null;
    }

    /**
     * @throws PlotException
     */
    public function getPlotPlayer(string $playerUUID) : ?PlotPlayer {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer !== null) {
            return $plotPlayer;
        }
        if (isset($this->plotPlayers["*"])) {
            return $this->plotPlayers["*"];
        }
        return null;
    }

    /**
     * @throws PlotException
     */
    public function isPlotOwnerExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    /**
     * @throws PlotException
     */
    public function isPlotOwner(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    /**
     * @throws PlotException
     */
    public function isPlotTrustedExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    /**
     * @throws PlotException
     */
    public function isPlotTrusted(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    /**
     * @throws PlotException
     */
    public function isPlotHelperExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    /**
     * @throws PlotException
     */
    public function isPlotHelper(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    /**
     * @throws PlotException
     */
    public function isPlotDeniedExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    /**
     * @throws PlotException
     */
    public function isPlotDenied(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    /**
     * @throws PlotException
     */
    public function addPlotPlayer(PlotPlayer $plotPlayer) : void {
        $this->loadPlotPlayers();
        $this->plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
    }

    /**
     * @throws PlotException
     */
    public function removePlotPlayer(string $playerUUID) : void {
        $this->loadPlotPlayers();
        unset($this->plotPlayers[$playerUUID]);
    }

    /**
     * @internal
     * @throws PlotException
     */
    public function loadPlotPlayers() : void {
        if ($this->plotPlayers !== null) {
            return;
        }
        $this->plotPlayers = CPlot::getInstance()->getProvider()->getPlotPlayers($this);
        if ($this->plotPlayers === null) {
            throw new PlotException($this, "Couldn't load plot players of plot " . $this->toString() . " from provider.");
        }
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
    }

    /**
     * @return array<string, BaseAttribute>
     * @throws PlotException
     */
    public function getFlags() : array {
        $this->loadFlags();
        return $this->flags;
    }

    /**
     * @throws PlotException
     */
    public function getFlagByID(string $flagID) : ?BaseAttribute {
        $this->loadFlags();
        if (!isset($this->flags[$flagID])) {
            return null;
        }
        return $this->flags[$flagID];
    }

    /**
     * @throws PlotException
     */
    public function getFlagNonNullByID(string $flagID) : ?BaseAttribute {
        $flag = $this->getFlagByID($flagID);
        if ($flag === null) {
            $flag = FlagManager::getInstance()->getFlagByID($flagID);
        }
        return $flag;
    }

    /**
     * @throws PlotException
     */
    public function addFlag(BaseAttribute $flag) : void {
        $this->loadFlags();
        $this->flags[$flag->getID()] = $flag;
    }

    /**
     * @throws PlotException
     */
    public function removeFlag(string $flagID) : void {
        $this->loadFlags();
        unset($this->flags[$flagID]);
    }

    /**
     * @internal
     * @throws PlotException
     */
    public function loadFlags() : void {
        if ($this->flags !== null) {
            return;
        }
        $this->flags = CPlot::getInstance()->getProvider()->getPlotFlags($this);
        if ($this->flags === null) {
            throw new PlotException($this,"Couldn't load flags of plot " . $this->toString() . " from provider.");
        }
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
    }

    /**
     * @return array<string, PlotRate>
     * @throws PlotException
     */
    public function getPlotRates() : array {
        $this->loadPlotRates();
        return $this->plotRates;
    }

    /**
     * @throws PlotException
     */
    public function addPlotRate(PlotRate $plotRate) : void {
        $this->loadPlotRates();
        $this->plotRates[$plotRate->toString()] = $plotRate;
    }

    /**
     * @internal
     * @throws PlotException
     */
    public function loadPlotRates() : void {
        if ($this->plotRates !== null) {
            return;
        }
        $this->plotRates = CPlot::getInstance()->getProvider()->getPlotRates($this);
        if ($this->plotRates === null) {
            throw new PlotException($this,"Couldn't load plot rates of plot " . $this->toString() . " from provider.");
        }
        CPlot::getInstance()->getProvider()->getPlotCache()->cacheObject($this->toString(), $this);
    }

    /**
     * @throws PlotException
     */
    public function teleportTo(Player $player, bool $toPlotCenter = false, bool $checkSpawnFlag = true) : bool {
        if (!$toPlotCenter && $checkSpawnFlag) {
            $flag = $this->getFlagNonNullByID(FlagIDs::FLAG_SPAWN);
            $relativeSpawnLocation = $flag->getValue();
            if ($relativeSpawnLocation instanceof Location) {
                $world = $this->getWorld();
                if ($world === null) {
                    return false;
                }
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

        $mergePlots = $this->getMergePlots();
        if (count($mergePlots) >= 1) {
            $northestPlot = $this;
            foreach ($mergePlots as $mergePlot) {
                if ($northestPlot->getZ() > $mergePlot->getZ()) {
                    $northestPlot = $mergePlot;
                }
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

    /**
     * @throws PlotException
     */
    public function isOnPlot(Position $position, bool $checkMerge = true) : bool {
        if (!$checkMerge) {
            return parent::isOnPlot($position);
        }

        foreach ($this->getMergePlots() as $mergePlot) {
            if ($mergePlot->isOnPlot($position)) {
                return true;
            }
        }

        $plot = self::fromPosition($position);
        if ($plot !== null && $this->isSame($plot)) {
            return true;
        }
        return false;
    }

    public function toBasePlot() : BasePlot {
        return new BasePlot($this->worldName, $this->x, $this->z);
    }

    public static function fromPosition(Position $position, bool $checkMerge = true) : ?self {
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            return null;
        }

        // check for: position = plot
        $basePlot = parent::fromPosition($position);
        if ($basePlot !== null) {
            return $basePlot->toPlot();
        }

        if (!$checkMerge) {
            return null;
        }

        // check for: position = road between plots in north (-z) and south (+z)
        $basePlotInNorth = parent::fromPosition($position->getSide(Facing::NORTH, $worldSettings->getRoadSize()));
        $basePlotInSouth = parent::fromPosition($position->getSide(Facing::SOUTH, $worldSettings->getRoadSize()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            $plotInNorth = $basePlotInNorth->toPlot();
            if ($plotInNorth === null) {
                return null;
            }
            if (!$plotInNorth->isSame($basePlotInSouth)) {
                return null;
            }
            return $plotInNorth;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = parent::fromPosition($position->getSide(Facing::WEST, $worldSettings->getRoadSize()));
        $basePlotInEast = parent::fromPosition($position->getSide(Facing::EAST, $worldSettings->getRoadSize()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            $plotInWest = $basePlotInWest->toPlot();
            if ($plotInWest === null) {
                return null;
            }
            if (!$plotInWest->isSame($basePlotInEast)) {
                return null;
            }
            return $plotInWest;
        }

        // check for: position = road center
        $basePlotInNorthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInNorthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthWest = parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthEast = parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            $plotInNorthWest = $basePlotInNorthWest->toPlot();
            if ($plotInNorthWest === null) {
                return null;
            }
            if (!$plotInNorthWest->isSame($basePlotInNorthEast) || !$plotInNorthWest->isSame($basePlotInSouthWest) || !$plotInNorthWest->isSame($basePlotInSouthEast)) {
                return null;
            }
            return $plotInNorthWest;
        }

        return null;
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["biomeID"] = $this->biomeID;
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
        $this->alias = $data["alias"];
        $this->mergePlots = unserialize($data["mergePlots"]);
        $this->plotPlayers = unserialize($data["plotPlayers"]);
        $this->flags = unserialize($data["flags"]);
        $this->plotRates = unserialize($data["plotRates"]);
    }
}