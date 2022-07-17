<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\plots\flags\implementation\BurningFlag;
use ColinHDev\CPlot\plots\flags\implementation\ExplosionFlag;
use ColinHDev\CPlot\plots\flags\implementation\FarewellFlag;
use ColinHDev\CPlot\plots\flags\implementation\FlowingFlag;
use ColinHDev\CPlot\plots\flags\implementation\GreetingFlag;
use ColinHDev\CPlot\plots\flags\implementation\GrowingFlag;
use ColinHDev\CPlot\plots\flags\implementation\ItemDropFlag;
use ColinHDev\CPlot\plots\flags\implementation\ItemPickupFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlayerInteractFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlotEnterFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlotLeaveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\plots\flags\implementation\ServerPlotFlag;
use ColinHDev\CPlot\plots\flags\implementation\SpawnFlag;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static BurningFlag BURNING()
 * @method static ExplosionFlag EXPLOSION()
 * @method static FarewellFlag FAREWELL()
 * @method static FlowingFlag FLOWING()
 * @method static GreetingFlag GREETING()
 * @method static GrowingFlag GROWING()
 * @method static ItemDropFlag ITEM_DROP()
 * @method static ItemPickupFlag ITEM_PICKUP()
 * @method static PlayerInteractFlag PLAYER_INTERACT()
 * @method static PlotEnterFlag PLOT_ENTER()
 * @method static PlotLeaveFlag PLOT_LEAVE()
 * @method static PveFlag PVE()
 * @method static PvpFlag PVP()
 * @method static ServerPlotFlag SERVER_PLOT()
 * @method static SpawnFlag SPAWN()
 */
final class Flags {
    use CloningRegistryTrait;

    private function __construct() {
    }

    protected static function register(string $flagID, Flag $flag) : void{
        self::_registryRegister($flagID, $flag);
    }

    protected static function setup() : void {
        $flagManager = FlagManager::getInstance();
        self::register(FlagIDs::FLAG_BURNING, $flagManager->getFlagByID(FlagIDs::FLAG_BURNING));
        self::register(FlagIDs::FLAG_EXPLOSION, $flagManager->getFlagByID(FlagIDs::FLAG_EXPLOSION));
        self::register(FlagIDs::FLAG_FAREWELL, $flagManager->getFlagByID(FlagIDs::FLAG_FAREWELL));
        self::register(FlagIDs::FLAG_FLOWING, $flagManager->getFlagByID(FlagIDs::FLAG_FLOWING));
        self::register(FlagIDs::FLAG_GREETING, $flagManager->getFlagByID(FlagIDs::FLAG_GREETING));
        self::register(FlagIDs::FLAG_GROWING, $flagManager->getFlagByID(FlagIDs::FLAG_GROWING));
        self::register(FlagIDs::FLAG_ITEM_DROP, $flagManager->getFlagByID(FlagIDs::FLAG_ITEM_DROP));
        self::register(FlagIDs::FLAG_ITEM_PICKUP, $flagManager->getFlagByID(FlagIDs::FLAG_ITEM_PICKUP));
        self::register(FlagIDs::FLAG_PLAYER_INTERACT, $flagManager->getFlagByID(FlagIDs::FLAG_PLAYER_INTERACT));
        self::register(FlagIDs::FLAG_PLOT_ENTER, $flagManager->getFlagByID(FlagIDs::FLAG_PLOT_ENTER));
        self::register(FlagIDs::FLAG_PLOT_LEAVE, $flagManager->getFlagByID(FlagIDs::FLAG_PLOT_LEAVE));
        self::register(FlagIDs::FLAG_PVE, $flagManager->getFlagByID(FlagIDs::FLAG_PVE));
        self::register(FlagIDs::FLAG_PVP, $flagManager->getFlagByID(FlagIDs::FLAG_PVP));
        self::register(FlagIDs::FLAG_SERVER_PLOT, $flagManager->getFlagByID(FlagIDs::FLAG_SERVER_PLOT));
        self::register(FlagIDs::FLAG_SPAWN, $flagManager->getFlagByID(FlagIDs::FLAG_SPAWN));
    }
}