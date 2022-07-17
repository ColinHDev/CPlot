<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\ResourceManager;
use InvalidArgumentException;
use pocketmine\utils\SingletonTrait;
use function gettype;
use function is_string;

class FlagManager {
    use SingletonTrait;

    /**
     * @var array<string, Flag>
     * @phpstan-var (non-empty-array<FlagIDs::*, Flag<mixed>>)
     */
    private array $flags = [];

    public function __construct() {
        $this->register($this->getFlagFromConfig(PvpFlag::FALSE()));
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
     * @return array<string, Flag<mixed>>
     */
    public function getFlags() : array {
        return $this->flags;
    }

    /**
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