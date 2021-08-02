<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class FlagManager {

    use SingletonTrait;

    /** @var BaseFlag[] */
    private array $flags = [];

    public function __construct() {
        $config = ResourceManager::getInstance()->getFlagsConfig();

        $this->register($config, FlagIDs::FLAG_TITLE, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLOT_ENTER, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLOT_LEAVE, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_MESSAGE, StringFlag::class);

        $this->register($config, FlagIDs::FLAG_SPAWN, PositionFlag::class);

        $this->register($config, FlagIDs::FLAG_ITEM_DROP, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_ITEM_PICKUP, BooleanFlag::class);

        $this->register($config, FlagIDs::FLAG_PVP, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_EXPLOSION, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_BURNING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_FLOWING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_GROWING, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_PLAYER_INTERACT, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_SERVER_PLOT, BooleanFlag::class);
        $this->register($config, FlagIDs::FLAG_CHECK_OFFLINETIME, BooleanFlag::class);

        $this->register($config, FlagIDs::FLAG_PLACE, ArrayFlag::class);
        $this->register($config, FlagIDs::FLAG_BREAK, ArrayFlag::class);
        $this->register($config, FlagIDs::FLAG_USE, ArrayFlag::class);
    }

    /**
     * @param Config    $config
     * @param string    $ID
     * @param string    $className
     */
    private function register(Config $config, string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseFlag::class);
        $this->flags[$ID] = new $className($ID, $config->get($ID));
    }

    /**
     * @param string $ID
     * @return BaseFlag | null
     */
    public function getFlagByID(string $ID) : ?BaseFlag {
        if (!isset($this->flags[$ID])) return null;
        return clone $this->flags[$ID];
    }
}