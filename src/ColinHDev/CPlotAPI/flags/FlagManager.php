<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\implementations\SpawnFlag;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class FlagManager {

    use SingletonTrait;

    /** @var class-string<BaseFlag>[] */
    private array $flags = [];

    public function __construct() {
        $config = ResourceManager::getInstance()->getFlagsConfig();

        $this->register($config, FlagIDs::FLAG_TITLE, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLOT_ENTER, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLOT_LEAVE, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_MESSAGE, StringFlag::class);

        $this->register($config, FlagIDs::FLAG_SPAWN, SpawnFlag::class);

        $this->register($config, FlagIDs::FLAG_ITEM_DROP, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_ITEM_PICKUP, BooleanFlag::class);

        $this->register($config, FlagIDs::FLAG_PVP, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PVE, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_EXPLOSION, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_BURNING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_FLOWING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_GROWING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLAYER_INTERACT, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_SERVER_PLOT, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_CHECK_INACTIVE, BooleanFlag::class);

        $this->register($config, FlagIDs::FLAG_PLACE, ArrayFlag::class);
        $this->register($config, FlagIDs::FLAG_BREAK, ArrayFlag::class);
        $this->register($config, FlagIDs::FLAG_USE, ArrayFlag::class);
    }

    /**
     * @param Config $config
     * @param string $ID
     * @param class-string<BaseFlag> $className
     */
    private function register(Config $config, string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseFlag::class);
        $className::init($ID, $config->get("category"), $config->get("type"), $config->get("description"), "cplot.flag." . $ID, $config->get("default"));
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