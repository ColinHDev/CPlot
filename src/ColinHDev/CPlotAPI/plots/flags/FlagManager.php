<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class FlagManager {
    use SingletonTrait;

    /** @var class-string<Flag>[] */
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
     * @param class-string<Flag> $className
     */
    private function register(string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseAttribute::class);
        /** @var class-string<Flag> $className */
        $this->flags[$ID] = $className;
    }

    /**
     * @return class-string<Flag>[]
     */
    public function getFlags() : array {
        return $this->flags;
    }

    public function getFlagByID(string $ID) : ?Flag {
        if (!isset($this->flags[$ID])) {
            return null;
        }
        return new $this->flags[$ID]();
    }
}