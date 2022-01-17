<?php

namespace ColinHDev\CPlotAPI\plots;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\world\Position;

class Plot extends BasePlot {

    private int $biomeID;
    private ?string $alias;

    /** @var array<string, MergePlot> */
    private array $mergePlots;
    /** @var array<string, PlotPlayer> */
    private array $plotPlayers;
    /** @var array<string, BaseAttribute> */
    private array $flags;
    /** @var array<string, PlotRate> */
    private array $plotRates;

    /**
     * @param array<string, MergePlot> $mergePlots
     * @param array<string, PlotPlayer> $plotPlayers
     * @param array<string, BaseAttribute> $flags
     * @param array<string, PlotRate> $plotRates
     */
    public function __construct(string $worldName, int $x, int $z, int $biomeID = BiomeIds::PLAINS, ?string $alias = null, array $mergePlots = [], array $plotPlayers = [], array $flags = [], array $plotRates = []) {
        parent::__construct($worldName, $x, $z);
        $this->biomeID = $biomeID;
        $this->alias = $alias;
        $this->mergePlots = $mergePlots;
        $this->plotPlayers = $plotPlayers;
        $this->flags = $flags;
        $this->plotRates = $plotRates;
    }

    public function getBiomeID() : int {
        return $this->biomeID;
    }

    public function getAlias() : ?string {
        return $this->alias;
    }

    /**
     * @return array<string, MergePlot>
     */
    public function getMergePlots() : array {
        return $this->mergePlots;
    }

    public function isMerged(BasePlot $plot) : bool {
        if ($this->isSame($plot, false)) {
            return true;
        }
        return isset($this->mergePlots[$plot->toString()]);
    }

    public function addMergePlot(MergePlot $mergedPlot) : void {
        $this->mergePlots[$mergedPlot->toString()] = $mergedPlot;
    }

    public function merge(self $plot) : \Generator {
        foreach (array_merge([$plot], $plot->getMergePlots()) as $mergePlot) {
            $mergePlot = MergePlot::fromBasePlot($mergePlot->toBasePlot(), $this->x, $this->z);
            yield DataProvider::getInstance()->addMergePlot($this, $mergePlot);
            $this->addMergePlot($mergePlot);
        }

        foreach ($plot->getPlotPlayers() as $mergePlotPlayer) {
            yield DataProvider::getInstance()->savePlotPlayer($this, $mergePlotPlayer);
            $this->addPlotPlayer($mergePlotPlayer);
        }

        foreach ($plot->getFlags() as $mergeFlag) {
            $flag = $this->getFlagByID($mergeFlag->getID());
            if ($flag === null) {
                $flag = $mergeFlag;
            } else {
                $flag = $flag->merge($mergeFlag->getValue());
            }
            yield DataProvider::getInstance()->savePlotFlag($this, $flag);
            $this->addFlag($flag);
        }

        foreach ($plot->getPlotRates() as $mergePlotRate) {
            yield DataProvider::getInstance()->savePlotRate($this, $mergePlotRate);
            $this->addPlotRate($mergePlotRate);
        }
    }

    /**
     * @return array<string, PlotPlayer>
     */
    public function getPlotPlayers() : array {
        return $this->plotPlayers;
    }

    /**
     * @return array<string, PlotPlayer>
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
     */
    public function getPlotOwners() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_OWNER);
    }

    public function hasPlotOwner() : bool {
        return count($this->getPlotOwners()) !== 0;
    }

    /**
     * @return array<string, PlotPlayer>
     */
    public function getPlotTrusted() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_TRUSTED);
    }

    /**
     * @return array<string, PlotPlayer>
     */
    public function getPlotHelpers() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_HELPER);
    }

    /**
     * @return array<string, PlotPlayer>
     */
    public function getPlotDenied() : array {
        return $this->getPlotPlayersByState(PlotPlayer::STATE_DENIED);
    }

    public function getPlotPlayerExact(string $playerUUID) : ?PlotPlayer {
        if (isset($this->plotPlayers[$playerUUID])) {
            return $this->plotPlayers[$playerUUID];
        }
        return null;
    }

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

    public function isPlotOwnerExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotOwner(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotTrustedExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotTrusted(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotHelperExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotHelper(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotDeniedExact(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayerExact($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function isPlotDenied(string $playerUUID) : bool {
        $plotPlayer = $this->getPlotPlayer($playerUUID);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function addPlotPlayer(PlotPlayer $plotPlayer) : void {
        $this->plotPlayers[$plotPlayer->getPlayerUUID()] = $plotPlayer;
    }

    public function removePlotPlayer(string $playerUUID) : void {
        unset($this->plotPlayers[$playerUUID]);
    }

    /**
     * @return array<string, BaseAttribute>
     */
    public function getFlags() : array {
        return $this->flags;
    }

    public function getFlagByID(string $flagID) : ?BaseAttribute {
        if (!isset($this->flags[$flagID])) {
            return null;
        }
        return $this->flags[$flagID];
    }

    public function getFlagNonNullByID(string $flagID) : ?BaseAttribute {
        $flag = $this->getFlagByID($flagID);
        if ($flag === null) {
            $flag = FlagManager::getInstance()->getFlagByID($flagID);
        }
        return $flag;
    }

    public function addFlag(BaseAttribute $flag) : void {
        $this->flags[$flag->getID()] = $flag;
    }

    public function removeFlag(string $flagID) : void {
        unset($this->flags[$flagID]);
    }

    /**
     * @return array<string, PlotRate>
     */
    public function getPlotRates() : array {
        return $this->plotRates;
    }

    public function addPlotRate(PlotRate $plotRate) : void {
        $this->plotRates[$plotRate->toString()] = $plotRate;
    }

    /**
     * @throws \RuntimeException when called outside of main thread.
     */
    public function teleportTo(Player $player, bool $toPlotCenter = false, bool $checkSpawnFlag = true) : \Generator {
        if (!$toPlotCenter && $checkSpawnFlag) {
            $flag = $this->getFlagNonNullByID(FlagIDs::FLAG_SPAWN);
            $relativeSpawn = $flag?->getValue();
            if ($relativeSpawn instanceof Location) {
                $world = $this->getWorld();
                if ($world === null) {
                    return false;
                }
                return $player->teleport(
                    Location::fromObject(
                        $relativeSpawn->addVector(yield $this->getPosition()),
                        $world,
                        $relativeSpawn->getYaw(),
                        $relativeSpawn->getPitch()
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
            return yield $northestPlot->teleportTo($player, $toPlotCenter);
        }

        return yield parent::teleportTo($player, $toPlotCenter);
    }

    public function isSame(BasePlot $plot, bool $checkMerge = true) : \Generator {
        if ($checkMerge && !$plot instanceof self) {
            $plot = (yield $plot->toPlot()) ?? $plot;
        }
        return parent::isSame($plot);
    }

    public function isOnPlot(Position $position, bool $checkMerge = true) : \Generator {
        if (!$checkMerge) {
            return yield parent::isOnPlot($position);
        }

        foreach ($this->getMergePlots() as $mergePlot) {
            if ((yield $mergePlot->isOnPlot($position))) {
                return true;
            }
        }

        $plot = yield self::fromPosition($position);
        return $plot !== null && (yield $this->isSame($plot));
    }

    public function toBasePlot() : BasePlot {
        return new BasePlot($this->worldName, $this->x, $this->z);
    }

    public static function fromPosition(Position $position, bool $checkMerge = true) : \Generator {
        $worldSettings = yield DataProvider::getInstance()->getWorld($position->getWorld()->getFolderName());
        if (!$worldSettings instanceof WorldSettings) {
            return null;
        }

        // check for: position = plot
        $basePlot = yield parent::fromPosition($position);
        if ($basePlot !== null) {
            return yield $basePlot->toPlot();
        }

        if (!$checkMerge) {
            return null;
        }

        // check for: position = road between plots in north (-z) and south (+z)
        $basePlotInNorth = yield parent::fromPosition($position->getSide(Facing::NORTH, $worldSettings->getRoadSize()));
        $basePlotInSouth = yield parent::fromPosition($position->getSide(Facing::SOUTH, $worldSettings->getRoadSize()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            $plotInNorth = yield $basePlotInNorth->toPlot();
            if ($plotInNorth === null) {
                return null;
            }
            if (!(yield $plotInNorth->isSame($basePlotInSouth))) {
                return null;
            }
            return $plotInNorth;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = yield parent::fromPosition($position->getSide(Facing::WEST, $worldSettings->getRoadSize()));
        $basePlotInEast = yield parent::fromPosition($position->getSide(Facing::EAST, $worldSettings->getRoadSize()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            $plotInWest = yield $basePlotInWest->toPlot();
            if ($plotInWest === null) {
                return null;
            }
            if (!(yield $plotInWest->isSame($basePlotInEast))) {
                return null;
            }
            return $plotInWest;
        }

        // check for: position = road center
        $basePlotInNorthWest = yield parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInNorthEast = yield parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthWest = yield parent::fromPosition(Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthEast = yield parent::fromPosition(Position::fromObject($position->add($worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            $plotInNorthWest = yield $basePlotInNorthWest->toPlot();
            if ($plotInNorthWest === null) {
                return null;
            }
            if (
                !(yield $plotInNorthWest->isSame($basePlotInNorthEast)) ||
                !(yield $plotInNorthWest->isSame($basePlotInSouthWest)) ||
                !(yield $plotInNorthWest->isSame($basePlotInSouthEast))
            ) {
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