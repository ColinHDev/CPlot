<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\implementations\BreakFlag;
use ColinHDev\CPlotAPI\flags\implementations\BurningFlag;
use ColinHDev\CPlotAPI\flags\implementations\CheckInactiveFlag;
use ColinHDev\CPlotAPI\flags\implementations\ExplosionFlag;
use ColinHDev\CPlotAPI\flags\implementations\FlowingFlag;
use ColinHDev\CPlotAPI\flags\implementations\GrowingFlag;
use ColinHDev\CPlotAPI\flags\implementations\ItemDropFlag;
use ColinHDev\CPlotAPI\flags\implementations\ItemPickupFlag;
use ColinHDev\CPlotAPI\flags\implementations\MessageFlag;
use ColinHDev\CPlotAPI\flags\implementations\PlaceFlag;
use ColinHDev\CPlotAPI\flags\implementations\PlayerInteractFlag;
use ColinHDev\CPlotAPI\flags\implementations\PlotEnterFlag;
use ColinHDev\CPlotAPI\flags\implementations\PlotLeaveFlag;
use ColinHDev\CPlotAPI\flags\implementations\PveFlag;
use ColinHDev\CPlotAPI\flags\implementations\PvpFlag;
use ColinHDev\CPlotAPI\flags\implementations\ServerPlotFlag;
use ColinHDev\CPlotAPI\flags\implementations\SpawnFlag;
use ColinHDev\CPlotAPI\flags\implementations\TitleFlag;
use ColinHDev\CPlotAPI\flags\implementations\UseFlag;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class FlagManager {

    use SingletonTrait;

    /** @var class-string<BaseFlag>[] */
    private array $flags = [];

    public function __construct() {
        $this->register(FlagIDs::FLAG_TITLE, TitleFlag::class);
        $this->register(FlagIDs::FLAG_PLOT_ENTER, PlotEnterFlag::class);
        $this->register(FlagIDs::FLAG_PLOT_LEAVE, PlotLeaveFlag::class);
        $this->register(FlagIDs::FLAG_MESSAGE, MessageFlag::class);

        $this->register(FlagIDs::FLAG_SPAWN, SpawnFlag::class);

        $this->register(FlagIDs::FLAG_ITEM_DROP, ItemDropFlag::class);
        $this->register(FlagIDs::FLAG_ITEM_PICKUP, ItemPickupFlag::class);

        $this->register(FlagIDs::FLAG_PVP, PvpFlag::class);
        $this->register(FlagIDs::FLAG_PVE, PveFlag::class);
        $this->register(FlagIDs::FLAG_EXPLOSION, ExplosionFlag::class);
        $this->register(FlagIDs::FLAG_BURNING, BurningFlag::class);
        $this->register(FlagIDs::FLAG_FLOWING, FlowingFlag::class);
        $this->register(FlagIDs::FLAG_GROWING, GrowingFlag::class);
        $this->register(FlagIDs::FLAG_PLAYER_INTERACT, PlayerInteractFlag::class);
        $this->register(FlagIDs::FLAG_SERVER_PLOT, ServerPlotFlag::class);
        $this->register(FlagIDs::FLAG_CHECK_INACTIVE, CheckInactiveFlag::class);

        $this->register(FlagIDs::FLAG_PLACE, PlaceFlag::class);
        $this->register(FlagIDs::FLAG_BREAK, BreakFlag::class);
        $this->register(FlagIDs::FLAG_USE, UseFlag::class);
    }

    /**
     * @param string $ID
     * @param class-string<BaseFlag> $className
     */
    private function register(string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseFlag::class);

        $flagData = ResourceManager::getInstance()->getFlagsConfig()->get($ID);
        /** @var class-string<BaseFlag> $className */
        $className::init($ID, "cplot.flag." . $ID, $flagData["default"]);
        $this->flags[$ID] = $className;
    }

    /**
     * @return class-string<BaseFlag>[]
     */
    public function getFlags() : array {
        return $this->flags;
    }

    public function getFlagByID(string $ID) : ?BaseFlag {
        if (!isset($this->flags[$ID])) return null;
        return new $this->flags[$ID];
    }
}