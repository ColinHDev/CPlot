<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\event\PlotBiomeChangeAsyncEvent;
use ColinHDev\CPlot\event\PlotBorderChangeAsyncEvent;
use ColinHDev\CPlot\event\PlotClearAsyncEvent;
use ColinHDev\CPlot\event\PlotClearedAsyncEvent;
use ColinHDev\CPlot\event\PlotMergeAsyncEvent;
use ColinHDev\CPlot\event\PlotMergedAsyncEvent;
use ColinHDev\CPlot\event\PlotResetAsyncEvent;
use ColinHDev\CPlot\event\PlotWallChangeAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\tasks\async\PlotBiomeChangeAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotClearAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotMergeAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotResetAsyncTask;
use ColinHDev\CPlot\tasks\async\PlotWallChangeAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\block\Block;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use SOFe\AwaitGenerator\Await;
use function assert;
use function unserialize;

class Plot extends BasePlot {

    private ?string $alias;

    /** @var array<string, MergePlot> */
    private array $mergePlots;
    private PlotPlayerContainer $plotPlayerContainer;
    /** @var array<string, BaseAttribute<mixed>> */
    private array $flags;
    /** @var array<string, PlotRate> */
    private array $plotRates;

    /**
     * @param array<string, MergePlot> $mergePlots
     * @param array<string, BaseAttribute<mixed>> $flags
     * @param array<string, PlotRate> $plotRates
     */
    public function __construct(string $worldName, WorldSettings $worldSettings, int $x, int $z, ?string $alias = null, array $mergePlots = [], ?PlotPlayerContainer $plotPlayerContainer = null, array $flags = [], array $plotRates = []) {
        parent::__construct($worldName, $worldSettings, $x, $z);
        $this->alias = $alias;
        $this->mergePlots = $mergePlots;
        $this->plotPlayerContainer = $plotPlayerContainer ?? new PlotPlayerContainer();
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
     * @return array<int, PlotPlayer>
     */
    public function getPlotPlayers() : array {
        return $this->plotPlayerContainer->getPlotPlayers();
    }

    /**
     * @return array<int, PlotPlayer>
     */
    public function getPlotOwners() : array {
        return $this->plotPlayerContainer->getPlotPlayersByState(PlotPlayer::STATE_OWNER);
    }

    public function hasPlotOwner() : bool {
        return count($this->getPlotOwners()) !== 0;
    }

    /**
     * @return array<int, PlotPlayer>
     */
    public function getPlotTrusted() : array {
        return $this->plotPlayerContainer->getPlotPlayersByState(PlotPlayer::STATE_TRUSTED);
    }

    /**
     * @return array<int, PlotPlayer>
     */
    public function getPlotHelpers() : array {
        return $this->plotPlayerContainer->getPlotPlayersByState(PlotPlayer::STATE_HELPER);
    }

    /**
     * @return array<int, PlotPlayer>
     */
    public function getPlotDenied() : array {
        return $this->plotPlayerContainer->getPlotPlayersByState(PlotPlayer::STATE_DENIED);
    }

    public function getPlotPlayerExact(Player|PlayerData|int $player) : ?PlotPlayer {
        return $this->plotPlayerContainer->getPlotPlayerExact($player);
    }

    public function getPlotPlayer(Player|PlayerData|int $player) : ?PlotPlayer {
        return $this->plotPlayerContainer->getPlotPlayer($player);
    }

    public function isPlotOwnerExact(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotOwner(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_OWNER;
    }

    public function isPlotTrustedExact(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotTrusted(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_TRUSTED;
    }

    public function isPlotHelperExact(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotHelper(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_HELPER;
    }

    public function isPlotDeniedExact(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayerExact($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function isPlotDenied(Player|PlayerData|int $player) : bool {
        $plotPlayer = $this->plotPlayerContainer->getPlotPlayer($player);
        if ($plotPlayer === null) {
            return false;
        }
        return $plotPlayer->getState() === PlotPlayer::STATE_DENIED;
    }

    public function addPlotPlayer(PlotPlayer $plotPlayer) : void {
        $this->plotPlayerContainer->addPlotPlayer($plotPlayer);
    }

    public function removePlotPlayer(PlotPlayer $plotPlayer) : void {
        $this->plotPlayerContainer->removePlotPlayer($plotPlayer);
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
     * Returns a {@see Location} at the edge of the plot where a player could be teleported to. If the plot is merged
     * with other plots, the edge of a most northern plot is used.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getTeleportLocation() : Location {
        if (count($this->mergePlots) >= 1) {
            $northestPlot = $this->toBasePlot();
            foreach ($this->mergePlots as $mergePlot) {
                if ($northestPlot->getZ() > $mergePlot->getZ()) {
                    $northestPlot = $mergePlot;
                }
            }
            return $northestPlot->getTeleportLocation();
        }
        return parent::getTeleportLocation();
    }

    /**
     * Returns a {@see Location} at the center of the plot where a player could be teleported to. If the plot is merged
     * with other plots, the center of a most northern plot is used.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function getCenterTeleportLocation() : Location {
        if (count($this->mergePlots) >= 1) {
            $northestPlot = $this->toBasePlot();
            foreach ($this->mergePlots as $mergePlot) {
                if ($northestPlot->getZ() > $mergePlot->getZ()) {
                    $northestPlot = $mergePlot;
                }
            }
            return $northestPlot->getCenterTeleportLocation();
        }
        return parent::getCenterTeleportLocation();
    }

    /**
     * This method can be used to teleport a player to the plot.
     * @param Player $player The player who should be teleported.
     * @param int $destination The destination where the player should be teleported to. A list of destinations can be
     *                         found in {@see PlotTeleportDestination}.
     * @phpstan-param TeleportDestination::* $destination
     * @return bool Returns TRUE if the player was successfully teleported or FALSE if not.
     * @throws \RuntimeException when called outside of main thread.
     */
    public function teleportTo(Player $player, int $destination = TeleportDestination::PLOT_SPAWN_OR_EDGE) : bool {
        if ($destination === TeleportDestination::PLOT_SPAWN_OR_EDGE || $destination === TeleportDestination::PLOT_SPAWN_OR_CENTER) {
            $flag = $this->getFlagByID(FlagIDs::FLAG_SPAWN);
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
        if ($destination === TeleportDestination::PLOT_SPAWN_OR_CENTER || $destination === TeleportDestination::PLOT_CENTER) {
            $location = $this->getCenterTeleportLocation();
        } else {
            $location = $this->getTeleportLocation();
            if ($destination === TeleportDestination::ROAD_EDGE) {
                $location = Location::fromObject(
                    $location->subtract(0, 0, 2),
                    $location->world, $location->yaw, $location->pitch
                );
            }
        }
        return $player->teleport($location);
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
                $task = new PlotBiomeChangeAsyncTask($this, $event->getBiomeID());
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
     * This method can be called to change the block the wall of a plot is made of.
     * @param Block $block The block the plot wall will be changed to.
     * @param callable|null $onSuccess Callback to be called when the plot wall was changed successfully.
     * @phpstan-param (callable(): void)|(callable(PlotWallChangeAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot wall could not be changed.
     * @phpstan-param (callable(): void)|(callable(PlotWallChangeAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function setWallBlock(Block $block, ?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($block, $onSuccess, $onError) {
                /** @phpstan-var PlotWallChangeAsyncEvent $event */
                $event = yield from PlotWallChangeAsyncEvent::create($this, $block);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                $task = new PlotWallChangeAsyncTask($this, $event->getBlock());
                $task->setCallback($onSuccess, $onError);
                Server::getInstance()->getAsyncPool()->submitTask($task);
            }
        );
    }

    /**
     * This method can be called to merge this plot with another one. By this, both plots' areas and data are merged together.
     * @param Plot $plotToMerge The plot this plot will be merged with. If there are any conflicts in both plots' data,
     *                          the data of the plot, which was provided as a parameter, will be discarded.
     * @param callable|null $onSuccess Callback to be called when the plot was reset successfully.
     * @phpstan-param (callable(): void)|(callable(PlotMergeAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot could not be reset.
     * @phpstan-param (callable(): void)|(callable(PlotMergeAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function merge(self $plotToMerge, ?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($plotToMerge, $onSuccess, $onError) {
                /** @phpstan-var PlotMergeAsyncEvent $event */
                $event = yield from PlotMergeAsyncEvent::create($this, $plotToMerge);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                $oldPlot = clone $this;
                yield from DataProvider::getInstance()->awaitPlotDeletion($plotToMerge);
                yield from $this->mergeData($plotToMerge);
                /** @phpstan-var PlotMergeAsyncTask $task */
                $task = yield from Await::promise(
                    static function(\Closure $onSuccess) use($oldPlot, $plotToMerge, $onError) : void {
                        $task = new PlotMergeAsyncTask($oldPlot, $plotToMerge);
                        $task->setCallback($onSuccess, $onError);
                        Server::getInstance()->getAsyncPool()->submitTask($task);
                    }
                );
                yield from PlotMergedAsyncEvent::create($this);
                if ($onSuccess !== null) {
                    $onSuccess($task);
                }
            }
        );
    }

    /**
     * @internal method to merge the data of this plot with the provided one.
     * @param Plot $plotToMerge The plot this plot will be merged with. If there are any conflicts in both plots' data,
     *                          the data of the plot, which was provided as a parameter, will be discarded.
     * @phpstan-return \Generator<mixed, mixed, mixed, void>
     */
    private function mergeData(self $plotToMerge) : \Generator {
        foreach (array_merge([$plotToMerge], $plotToMerge->getMergePlots()) as $mergePlot) {
            $mergePlot = MergePlot::fromBasePlot($mergePlot->toBasePlot(), $this->x, $this->z);
            $this->addMergePlot($mergePlot);
            yield from DataProvider::getInstance()->addMergePlot($this, $mergePlot);
        }

        foreach ($plotToMerge->getPlotPlayers() as $mergePlotPlayer) {
            if (!($this->getPlotPlayerExact($mergePlotPlayer->getPlayerData()) instanceof PlotPlayer)) {
                $this->addPlotPlayer($mergePlotPlayer);
                yield from DataProvider::getInstance()->savePlotPlayer($this, $mergePlotPlayer);
            }
        }

        foreach ($plotToMerge->getFlags() as $mergeFlag) {
            $flag = $this->getFlagByID($mergeFlag->getID());
            if ($flag === null) {
                $flag = $mergeFlag;
            } else {
                $flag = $flag->merge($mergeFlag->getValue());
            }
            $this->addFlag($flag);
            yield from DataProvider::getInstance()->savePlotFlag($this, $flag);
        }

        foreach ($plotToMerge->getPlotRates() as $mergePlotRate) {
            $this->addPlotRate($mergePlotRate);
            yield from DataProvider::getInstance()->savePlotRate($this, $mergePlotRate);
        }
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
                /** @phpstan-var PlotClearAsyncTask $task */
                $task = yield from Await::promise(
                    function(\Closure $onSuccess) use($onError) : void {
                        $task = new PlotClearAsyncTask($this);
                        $task->setCallback($onSuccess, $onError);
                        Server::getInstance()->getAsyncPool()->submitTask($task);
                    }
                );
                yield from PlotClearedAsyncEvent::create($this);
                if ($onSuccess !== null) {
                    $onSuccess($task);
                }
            }
        );
    }

    /**
     * This method can be called to reset a plot. By this, both the plot's area and all data are completely reset.
     * @param callable|null $onSuccess Callback to be called when the plot was reset successfully.
     * @phpstan-param (callable(): void)|(callable(PlotResetAsyncTask): void)|null $onSuccess
     * @param callable|null $onError Callback to be called when the plot could not be reset.
     * @phpstan-param (callable(): void)|(callable(PlotResetAsyncTask|null=): void)|null $onError
     * @throws \RuntimeException when called outside of main thread.
     */
    public function reset(?callable $onSuccess = null, ?callable $onError = null) : void {
        Await::f2c(
            function () use ($onSuccess, $onError) {
                /** @phpstan-var PlotResetAsyncEvent $event */
                $event = yield from PlotResetAsyncEvent::create($this);
                if ($event->isCancelled()) {
                    if ($onError !== null) {
                        $onError();
                    }
                    return;
                }
                yield from DataProvider::getInstance()->awaitPlotDeletion($this);
                $task = new PlotResetAsyncTask($this);
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
     * @deprecated
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
     * @deprecated
     * @phpstan-return \Generator<mixed, mixed, mixed, Plot|null>
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
        $data["plotPlayers"] = serialize($this->plotPlayerContainer);
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
        $plotPlayerContainer = unserialize($data["plotPlayers"], ["allowed_classes" => [PlotPlayerContainer::class]]);
        assert($plotPlayerContainer instanceof PlotPlayerContainer);
        $this->plotPlayerContainer = $plotPlayerContainer;
        /** @phpstan-var array<string, BaseAttribute<mixed>> $flags */
        $flags = unserialize($data["flags"], ["allowed_classes" => false]);
        $this->flags = $flags;
        /** @phpstan-var array<string, PlotRate> $plotRates */
        $plotRates = unserialize($data["plotRates"], ["allowed_classes" => false]);
        $this->plotRates = $plotRates;
    }
}