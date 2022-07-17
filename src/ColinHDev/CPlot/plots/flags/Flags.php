<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\plots\flags\implementation\BurningFlag;
use ColinHDev\CPlot\plots\flags\implementation\ExplosionFlag;
use ColinHDev\CPlot\plots\flags\implementation\FlowingFlag;
use ColinHDev\CPlot\plots\flags\implementation\GrowingFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlayerInteractFlag;
use ColinHDev\CPlot\plots\flags\implementation\PveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\plots\flags\implementation\ServerPlotFlag;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static BurningFlag BURNING()
 * @method static ExplosionFlag EXPLOSION()
 * @method static FlowingFlag FLOWING()
 * @method static GrowingFlag GROWING()
 * @method static PlayerInteractFlag PLAYERINTERACT()
 * @method static PveFlag PVE()
 * @method static PvpFlag PVP()
 * @method static ServerPlotFlag SERVERPLOT()
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
        self::register(FlagIDs::FLAG_FLOWING, $flagManager->getFlagByID(FlagIDs::FLAG_FLOWING));
        self::register(FlagIDs::FLAG_GROWING, $flagManager->getFlagByID(FlagIDs::FLAG_GROWING));
        self::register(FlagIDs::FLAG_PLAYER_INTERACT, $flagManager->getFlagByID(FlagIDs::FLAG_PLAYER_INTERACT));
        self::register(FlagIDs::FLAG_PVE, $flagManager->getFlagByID(FlagIDs::FLAG_PVE));
        self::register(FlagIDs::FLAG_PVP, $flagManager->getFlagByID(FlagIDs::FLAG_PVP));
        self::register(FlagIDs::FLAG_SERVER_PLOT, $flagManager->getFlagByID(FlagIDs::FLAG_SERVER_PLOT));
    }
}