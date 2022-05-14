<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\event\PlotBiomeChangeAsyncEvent;
use ColinHDev\CPlot\event\PlotBorderChangeAsyncEvent;
use ColinHDev\CPlot\event\PlotClearAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\tasks\async\PlotBiomeChangeAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotClearAsyncTask;
use ColinHDev\CPlot\worlds\NonWorldSettings;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\block\Block;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class Plot extends BasePlot {

    private ?string $alias;

    /** @var array<string, MergePlot> */
    private array $mergePlots;
    /** @var array<string, PlotPlayer> */
    private array $plotPlayers;
    /** @var array<string, BaseAttribute<mixed>> */
    private array $flags;
    /** @var array<string, PlotRate> */
    private array $plotRates;

    /**
     * @param array<string, MergePlot> $mergePlots
     * @param array<string, PlotPlayer> $plotPlayers
     * @param array<string, BaseAttribute<mixed>> $flags
     * @param array<string, PlotRate> $plotRates
     */
    public function __construct(string $worldName, WorldSettings $worldSettings, int $x, int $z, ?string $alias = null, array $mergePlots = [], array $plotPlayers = [], array $flags = [], array $plotRates = []) {
        parent::__construct($worldName, $worldSettings, $x, $z);
        $this->alias = $alias;
        $this->mergePlots = $mergePlots;
        $this->plotPlayers = $plotPlayers;
        $this->flags = $flags;
        $this->plotRates = $plotRates;
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

    /**
     * @phpstan-return \Generator<int, mixed, void, void>
     */
    public function merge(self $plot) : \Generator {
        foreach (array_merge([$plot], $plot->getMergePlots()) as $mergePlot) {
            $mergePlot = MergePlot::fromBasePlot($mergePlot->toBasePlot(), $this->x, $this->z);
            $this->addMergePlot($mergePlot);
            yield DataProvider::getInstance()->addMergePlot($this, $mergePlot);
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
                $plotPlayers[$plotPlayer->toString()] = $plotPlayer;
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

    public function getPlotPlayerExact(Player|PlayerData|string $player) : ?PlotPlayer {
        if ($player instanceof Player) {
            $identifier = PlayerData::getIdentifierFromPlayer($player);
        } else if ($player instanceof PlayerData) {
            $identifier = PlayerData::getIdentifierFromPlayerData($player);
        } else {
            $identifier = $player;
        }
        if (isset($this->plotPlayers[$identifier])) {
            return $this->plotPlayers[$identifier];
        }
        return null;
    }

    public function getPlotPlayer(Player|PlayerData|string $player) : ?PlotPlayer {
        $plotPlayer = $this->getPlotPlayerExact($player);
        if ($plotPlayer !== null) {
            return $plotPlayer;
        }
        if (isset($this->plotPlayers["*"])) {
            return $this->plotPlayers["*"];
        }
        return null;
    }

    public function isPlotOwnerExact(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotOwner(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotTrustedExact(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotTrusted(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotHelperExact(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotHelper(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotDeniedExact(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function isPlotDenied(Player|PlayerData|string $player) : bool {
        $plotPlayer = $this->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function addPlotPlayer(PlotPlayer $plotPlayer) : void {
        $this->plotPlayers[$plotPlayer->toString()] = $plotPlayer;
    }

    public function removePlotPlayer(string $playerIdentifier) : void {
        unset($this->plotPlayers[$playerIdentifier]);
    }

    /**
     * @phpstan-return array<string, BaseAttribute<mixed>>
     */
    public function getFlags() : array {
        return $this->flags;
    }

    /**
     * @phpstan-return BaseAttribute<mixed>|null
     */
    public function getFlagByID(string $flagID) : ?BaseAttribute {
        if (!isset($this->flags[$flagID])) {
            return null;
        }
        return $this->flags[$flagID];
    }

    /**
     * @phpstan-return BaseAttribute<mixed>|null
     */
    public function getFlagNonNullByID(string $flagID) : ?BaseAttribute {
        $flag = $this->getFlagByID($flagID);
        if ($flag === null) {
            $flag = FlagManager::getInstance()->getFlagByID($flagID);
        }
        return $flag;
    }

    /**
     * @phpstan-template TAttributeValue
     * @phpstan-param BaseAttribute<TAttributeValue> $flag
     */
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
    public function teleportTo(Player $player, bool $toPlotCenter = false, bool $checkSpawnFlag = true) : bool {
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
                        $relativeSpawn->addVector($this->getVector3()),
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
            return $northestPlot->teleportTo($player, $toPlotCenter);
        }

        return parent::teleportTo($player, $toPlotCenter);
    }

    /**
     * This method can be called to change the biome of a plot. By this, the biome of the entire plot area is changed.
     * @param int $biomeID The ID of the biome the plot will be changed to.
     * @phpstan-param BiomeIds::* $biomeID
     * @param callable|null $onSuccess Callback to be called when the plot biome was changed successfully.
     * @phpstan-param (callable(): void)|(callable(PlotBiomeChangeAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot biome could not be changed.
     * @phpstan-param (callable(): void)|(callable(PlotBiomeChangeAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function setBiome(int $biomeID, ?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($biomeID, $onSuccess, $onError) {
                /** @phpstan-var PlotBiomeChangeAsyncEvent $event */
                $event = yield from PlotBiomeChangeAsyncEvent::create($this, $biomeID);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                $world = $this->getWorld();
                assert($world instanceof World);
                $task = new PlotBiomeChangeAsyncTask($world, $this, $event->getBiomeID());
                $task->setCallback($onSuccess, $onError);
                Server::getInstance()->getAsyncPool()->submitTask($task);
            }
        );
    }

    /**
     * This method can be called to change the block the border of a plot is made of.
     * @param Block $block The block the plot border will be changed to.
     * @param callable|null $onSuccess Callback to be called when the plot border was changed successfully.
     * @phpstan-param (callable(): void)|(callable(PlotBorderChangeAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot border could not be changed.
     * @phpstan-param (callable(): void)|(callable(PlotBorderChangeAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function setBorderBlock(Block $block, ?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($block, $onSuccess, $onError) {
                /** @phpstan-var PlotBorderChangeAsyncEvent $event */
                $event = yield from PlotBorderChangeAsyncEvent::create($this, $block);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                $task = new PlotBorderChangeAsyncTask($this, $event->getBlock());
                $task->setCallback($onSuccess, $onError);
                Server::getInstance()->getAsyncPool()->submitTask($task);
            }
        );
    }

    /**
     * This method can be called to clear a plot. By this, the plot area is completely reset while all data is kept.
     * @param callable|null $onSuccess Callback to be called when the plot was cleared successfully.
     * @phpstan-param (callable(): void)|(callable(PlotClearAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot could not be cleared.
     * @phpstan-param (callable(): void)|(callable(PlotClearAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function clear(?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($onSuccess, $onError) {
                /** @phpstan-var PlotClearAsyncEvent $event */
                $event = yield from PlotClearAsyncEvent::create($this);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                $world = $this->getWorld();
                assert($world instanceof World);
                $task = new PlotClearAsyncTask($world, $this->worldSettings, $this);
                $task->setCallback($onSuccess, $onError);
                Server::getInstance()->getAsyncPool()->submitTask($task);
            }
        );
    }

    public function isSame(BasePlot $plot, bool $checkMerge = true) : bool {
        if ($checkMerge && !$plot instanceof self) {
            $plot = $plot->toSyncPlot() ?? $plot;
        }
        return parent::isSame($plot);
    }

    public function isOnPlot(Position $position, bool $checkMerge = true) : bool {
        if (parent::isOnPlot($position)) {
            return true;
        }
        if (!$checkMerge) {
            return false;
        }

        /** @var MergePlot $mergePlot */
        foreach ($this->getMergePlots() as $mergePlot) {
            if (($mergePlot->isOnPlot($position))) {
                return true;
            }
        }

        $vector3 = $position->asVector3();
        $northernBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $vector3->getSide(Facing::NORTH, $this->worldSettings->getRoadSize()));
        $southernBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $vector3->getSide(Facing::SOUTH, $this->worldSettings->getRoadSize()));
        if ($northernBasePlot instanceof BasePlot && $southernBasePlot instanceof BasePlot) {
            return $this->isMerged($northernBasePlot) && $this->isMerged($southernBasePlot);
        }

        $westernBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $vector3->getSide(Facing::WEST, $this->worldSettings->getRoadSize()));
        $easternBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $vector3->getSide(Facing::EAST, $this->worldSettings->getRoadSize()));
        if ($westernBasePlot instanceof BasePlot && $easternBasePlot instanceof BasePlot) {
            return $this->isMerged($westernBasePlot) && $this->isMerged($easternBasePlot);
        }

        $northwesternBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $position->add(- $this->worldSettings->getRoadSize(), 0, - $this->worldSettings->getRoadSize()));
        $northeasternBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $position->add($this->worldSettings->getRoadSize(), 0, - $this->worldSettings->getRoadSize()));
        $southwesternBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $position->add(- $this->worldSettings->getRoadSize(), 0, $this->worldSettings->getRoadSize()));
        $southeasternBasePlot = parent::fromVector3($this->worldName, $this->worldSettings, $position->add($this->worldSettings->getRoadSize(), 0, $this->worldSettings->getRoadSize()));
        if ($northwesternBasePlot instanceof BasePlot && $northeasternBasePlot instanceof BasePlot && $southwesternBasePlot instanceof BasePlot && $southeasternBasePlot instanceof BasePlot) {
            return $this->isMerged($northwesternBasePlot) && $this->isMerged($northeasternBasePlot) && $this->isMerged($southwesternBasePlot) && $this->isMerged($southeasternBasePlot);
        }

        return false;
    }

    public function toBasePlot() : BasePlot {
        return new BasePlot($this->worldName, $this->worldSettings, $this->x, $this->z);
    }

    /**
     * Tries to load a {@see Plot} from a given {@see Position}. Returns an instance of {@see Plot} on success, an
     * instance of {@see BasePlot} if the plot could not be loaded from the cache {@see DataProvider::getPlotCache()} or
     * null if no plot is at that positon.
     */
    public static function loadFromPositionIntoCache(Position $position, bool $checkMerge = true) : self|parent|null {
        $worldName = $position->getWorld()->getFolderName();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($worldName);
        if (!$worldSettings instanceof WorldSettings) {
            return null;
        }

        // check for: position = plot
        $basePlot = parent::fromVector3($worldName, $worldSettings, $position);
        if ($basePlot !== null) {
            return $basePlot->toSyncPlot() ?? $basePlot;
        }

        if (!$checkMerge) {
            return null;
        }

        // check for: position = road between plots in north (-z) and south (+z)
        $basePlotInNorth = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::NORTH, $worldSettings->getRoadSize()));
        $basePlotInSouth = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::SOUTH, $worldSettings->getRoadSize()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            $plotInNorth = $basePlotInNorth->toSyncPlot();
            $plotInSouth = $basePlotInSouth->toSyncPlot();
            if ($plotInNorth === null) {
                return $basePlotInNorth;
            }
            if ($plotInSouth === null) {
                return $basePlotInSouth;
            }
            if (!$plotInNorth->isSame($plotInSouth)) {
                return null;
            }
            return $plotInNorth;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::WEST, $worldSettings->getRoadSize()));
        $basePlotInEast = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::EAST, $worldSettings->getRoadSize()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            $plotInWest = $basePlotInWest->toSyncPlot();
            $plotInEast = $basePlotInEast->toSyncPlot();
            if ($plotInWest === null) {
                return $basePlotInWest;
            }
            if ($plotInEast === null) {
                return $basePlotInEast;
            }
            if (!$plotInWest->isSame($plotInEast)) {
                return null;
            }
            return $plotInWest;
        }

        // check for: position = road center
        $basePlotInNorthWest = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInNorthEast = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add($worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthWest = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthEast = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add($worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            $plotInNorthWest = $basePlotInNorthWest->toSyncPlot();
            $plotInNorthEast = $basePlotInNorthEast->toSyncPlot();
            $plotInSouthWest = $basePlotInSouthWest->toSyncPlot();
            $plotInSouthEast = $basePlotInSouthEast->toSyncPlot();
            if ($plotInNorthWest === null) {
                return $basePlotInNorthWest;
            }
            if ($plotInNorthEast === null) {
                return $basePlotInNorthEast;
            }
            if ($plotInSouthWest === null) {
                return $basePlotInSouthWest;
            }
            if ($plotInSouthEast === null) {
                return $basePlotInSouthEast;
            }
            if (
                !$plotInNorthWest->isSame($plotInNorthEast) ||
                !$plotInNorthWest->isSame($plotInSouthWest) ||
                !$plotInNorthWest->isSame($plotInSouthEast)
            ) {
                return null;
            }
            return $plotInNorthWest;
        }

        return null;
    }

    /**
     * @phpstan-return \Generator<int, mixed, WorldSettings|NonWorldSettings|Plot|null, Plot|null>
     */
    public static function awaitFromPosition(Position $position, bool $checkMerge = true) : \Generator {
        $worldName = $position->getWorld()->getFolderName();
        $worldSettings = yield DataProvider::getInstance()->awaitWorld($position->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            return null;
        }
        // check for: position = plot
        $basePlot = parent::fromVector3($worldName, $worldSettings, $position);
        if ($basePlot !== null) {
            /** @var Plot|null $plot */
            $plot = yield $basePlot->toAsyncPlot();
            return $plot;
        }

        if (!$checkMerge) {
            return null;
        }

        // check for: position = road between plots in north (-z) and south (+z)
        $basePlotInNorth = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::NORTH, $worldSettings->getRoadSize()));
        $basePlotInSouth = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::SOUTH, $worldSettings->getRoadSize()));
        if ($basePlotInNorth !== null && $basePlotInSouth !== null) {
            /** @phpstan-var Plot|null $plotInNorth */
            $plotInNorth = yield $basePlotInNorth->toAsyncPlot();
            /** @phpstan-var Plot|null $plotInSouth */
            $plotInSouth = yield $basePlotInSouth->toAsyncPlot();
            if ($plotInNorth === null || $plotInSouth === null) {
                return null;
            }
            if (!$plotInNorth->isSame($plotInSouth)) {
                return null;
            }
            return $plotInNorth;
        }

        // check for: position = road between plots in west (-x) and east (+x)
        $basePlotInWest = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::WEST, $worldSettings->getRoadSize()));
        $basePlotInEast = parent::fromVector3($worldName, $worldSettings, $position->getSide(Facing::EAST, $worldSettings->getRoadSize()));
        if ($basePlotInWest !== null && $basePlotInEast !== null) {
            /** @phpstan-var Plot|null $plotInWest */
            $plotInWest = yield $basePlotInWest->toAsyncPlot();
            /** @phpstan-var Plot|null $plotInEast */
            $plotInEast = yield $basePlotInEast->toAsyncPlot();
            if ($plotInWest === null || $plotInEast === null) {
                return null;
            }
            if (!$plotInWest->isSame($plotInEast)) {
                return null;
            }
            return $plotInWest;
        }

        // check for: position = road center
        $basePlotInNorthWest = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInNorthEast = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add($worldSettings->getRoadSize(), 0, - $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthWest = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add(- $worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        $basePlotInSouthEast = parent::fromVector3($worldName, $worldSettings, Position::fromObject($position->add($worldSettings->getRoadSize(), 0, $worldSettings->getRoadSize()), $position->getWorld()));
        if ($basePlotInNorthWest !== null && $basePlotInNorthEast !== null && $basePlotInSouthWest !== null && $basePlotInSouthEast !== null) {
            /** @phpstan-var Plot|null $plotInNorthWest */
            $plotInNorthWest = yield $basePlotInNorthWest->toAsyncPlot();
            /** @phpstan-var Plot|null $plotInNorthEast */
            $plotInNorthEast = yield $basePlotInNorthEast->toAsyncPlot();
            /** @phpstan-var Plot|null $plotInSouthWest */
            $plotInSouthWest = yield $basePlotInSouthWest->toAsyncPlot();
            /** @phpstan-var Plot|null $plotInSouthEast */
            $plotInSouthEast = yield $basePlotInSouthEast->toAsyncPlot();
            if ($plotInNorthWest === null || $plotInNorthEast === null || $plotInSouthWest === null || $plotInSouthEast === null) {
                return null;
            }
            if (
                !$plotInNorthWest->isSame($plotInNorthEast) ||
                !$plotInNorthWest->isSame($plotInSouthWest) ||
                !$plotInNorthWest->isSame($plotInSouthEast)
            ) {
                return null;
            }
            return $plotInNorthWest;
        }

        return null;
    }

    /**
     * @phpstan-return array{worldName: string, worldSettings: string, x: int, z: int, alias: string|null, mergePlots: string, plotPlayers: string, flags: string, plotRates: string}
     */
    public function __serialize() : array {
        $data = parent::__serialize();
        $data["alias"] = $this->alias;
        $data["mergePlots"] = serialize($this->mergePlots);
        $data["plotPlayers"] = serialize($this->plotPlayers);
        $data["flags"] = serialize($this->flags);
        $data["plotRates"] = serialize($this->plotRates);
        return $data;
    }

    /**
     * @phpstan-param array{worldName: string, worldSettings: string, x: int, z: int, alias: string|null, mergePlots: string, plotPlayers: string, flags: string, plotRates: string} $data
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->alias = $data["alias"];
        /** @phpstan-var array<string, MergePlot> $mergePlots */
        $mergePlots = unserialize($data["mergePlots"], ["allowed_classes" => false]);
        $this->mergePlots = $mergePlots;
        /** @phpstan-var array<string, PlotPlayer> $plotPlayers */
        $plotPlayers = unserialize($data["plotPlayers"], ["allowed_classes" => false]);
        $this->plotPlayers = $plotPlayers;
        /** @phpstan-var array<string, BaseAttribute<mixed>> $flags */
        $flags = unserialize($data["flags"], ["allowed_classes" => false]);
        $this->flags = $flags;
        /** @phpstan-var array<string, PlotRate> $plotRates */
        $plotRates = unserialize($data["plotRates"], ["allowed_classes" => false]);
        $this->plotRates = $plotRates;
    }
}