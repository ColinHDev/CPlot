<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\plots\flags\implementations\BreakFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\BurningFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\CheckInactiveFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\ExplosionFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\FlowingFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\GrowingFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\ItemDropFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\ItemPickupFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\MessageFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PlaceFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PlayerInteractFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PlotEnterFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PlotLeaveFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PveFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\PvpFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\ServerPlotFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\SpawnFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\TitleFlag;
use ColinHDev\CPlotAPI\plots\flags\implementations\UseFlag;
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
        /** @var class-string<BaseFlag> $className */
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