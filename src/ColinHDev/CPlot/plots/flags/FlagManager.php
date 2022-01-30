<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\BlockListAttribute;
use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\attributes\LocationAttribute;
use ColinHDev\CPlot\attributes\StringAttribute;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class FlagManager {
    use SingletonTrait;

    /** @var array<string, BaseAttribute<mixed>> */
    private array $flags = [];

    public function __construct() {
        $this->register(FlagIDs::FLAG_TITLE, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_PLOT_ENTER, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_PLOT_LEAVE, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_MESSAGE, StringAttribute::class);

        $this->register(FlagIDs::FLAG_SPAWN, LocationAttribute::class);

        $this->register(FlagIDs::FLAG_ITEM_DROP, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_ITEM_PICKUP, BooleanAttribute::class);

        $this->register(FlagIDs::FLAG_PVP, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_PVE, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_EXPLOSION, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_BURNING, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_FLOWING, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_GROWING, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_PLAYER_INTERACT, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_SERVER_PLOT, BooleanAttribute::class);
        $this->register(FlagIDs::FLAG_CHECK_INACTIVE, BooleanAttribute::class);

        $this->register(FlagIDs::FLAG_PLACE, BlockListAttribute::class);
        $this->register(FlagIDs::FLAG_BREAK, BlockListAttribute::class);
        $this->register(FlagIDs::FLAG_USE, BlockListAttribute::class);
    }

    /**
     * @param string $ID
     * @param class-string<BaseAttribute<mixed>> $className
     */
    private function register(string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseAttribute::class);
        $this->flags[$ID] = new $className(
            $ID,
            "cplot.flag." . $ID,
            ResourceManager::getInstance()->getConfig()->getNested("flag." . $ID)
        );
    }

    /**
     * @return array<string, BaseAttribute<mixed>>
     */
    public function getFlags() : array {
        return $this->flags;
    }

    public function getFlagByID(string $ID) : ?BaseAttribute {
        if (!isset($this->flags[$ID])) {
            return null;
        }
        return clone $this->flags[$ID];
    }
}