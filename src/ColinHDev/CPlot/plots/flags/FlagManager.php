<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
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
use ColinHDev\CPlot\ResourceManager;
use InvalidArgumentException;
use pocketmine\utils\SingletonTrait;
use function array_map;
use function gettype;
use function is_string;

final class FlagManager {
    use SingletonTrait;

    /**
     * @var array<string, Flag>
     * @phpstan-var (non-empty-array<FlagIDs::*, Flag<mixed>>)
     */
    private array $flags = [];

    public function __construct() {
        $this->register($this->getFlagFromConfig(BurningFlag::FALSE()));
        $this->register($this->getFlagFromConfig(ExplosionFlag::FALSE()));
        $this->register($this->getFlagFromConfig(FarewellFlag::EMPTY()));
        $this->register($this->getFlagFromConfig(FlowingFlag::TRUE()));
        $this->register($this->getFlagFromConfig(GreetingFlag::EMPTY()));
        $this->register($this->getFlagFromConfig(GrowingFlag::TRUE()));
        $this->register($this->getFlagFromConfig(ItemDropFlag::TRUE()));
        $this->register($this->getFlagFromConfig(ItemPickupFlag::TRUE()));
        $this->register($this->getFlagFromConfig(PlayerInteractFlag::FALSE()));
        $this->register($this->getFlagFromConfig(PlotEnterFlag::FALSE()));
        $this->register($this->getFlagFromConfig(PlotLeaveFlag::FALSE()));
        $this->register($this->getFlagFromConfig(PveFlag::FALSE()));
        $this->register($this->getFlagFromConfig(PvpFlag::FALSE()));
        $this->register($this->getFlagFromConfig(ServerPlotFlag::FALSE()));
    }

    /**
     * @internal method to create a {@see Flag} instance with the default value defined in the config file.
     * @phpstan-template TFlag of Flag
     * @phpstan-template TFlagValue of mixed
     * @phpstan-param TFlag<TFlagValue> $flag
     * @phpstan-return TFlag<TFlagValue>
     * @throws InvalidArgumentException if the given default value is not valid for the given flag.
     */
    private function getFlagFromConfig(Flag $flag) : Flag {
        $default = ResourceManager::getInstance()->getConfig()->getNested("flag." . $flag->getID());
        if ($default === null) {
            return $flag;
        }
        if (!is_string($default)) {
            throw new InvalidArgumentException(
                "Expected type of default value for flag " . $flag->getID() . " to be string, " . gettype($default) . " given in config file under \"flag." . $flag->getID() . "\"."
            );
        }
        try {
            $parsedValue = $flag->parse($default);
        } catch(AttributeParseException) {
            throw new InvalidArgumentException(
                "Failed to parse default value for flag " . $flag->getID() . ". Value \"" . $default . "\" given in config file under \"flag." . $flag->getID() . "\" was not accepted."
            );
        }
        return $flag->createInstance($parsedValue);
    }

    /**
     * Registers a {@see Flag} to the {@see FlagManager}.
     * @param Flag $flag The flag to register.
     * @phpstan-param Flag<mixed> $flag
     */
    public function register(Flag $flag) : void {
        $this->flags[$flag->getID()] = $flag;
    }

    /**
     * Returns all registered {@see Flag} instances with their value being the flag's default value.
     * @phpstan-return array<string, Flag<mixed>>
     */
    public function getFlags() : array {
        return array_map(
            static function(Flag $flag) : Flag {
                return clone $flag;
            },
            $this->flags
        );
    }

    /**
     * Returns the {@see Flag} with the given ID with its value being the its default value.
     * @phpstan-param string $ID
     * @phpstan-return ($ID is FlagIDs::* ? Flag<mixed> : null)
     */
    public function getFlagByID(string $ID) : ?Flag {
        if (!isset($this->flags[$ID])) {
            return null;
        }
        return clone $this->flags[$ID];
    }
}