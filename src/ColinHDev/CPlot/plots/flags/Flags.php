<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static PvpFlag Pvp()
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
        self::register(FlagIDs::FLAG_PVP, $flagManager->getFlagByID(FlagIDs::FLAG_PVP));
    }
}