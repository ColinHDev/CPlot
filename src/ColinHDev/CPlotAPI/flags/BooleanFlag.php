<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\player\Player;

class BooleanFlag extends BaseFlag {

    protected bool $default;
    protected ?bool $value = null;

    public function __construct(string $ID, array $data, string $permission) {
        parent::__construct($ID, $data, $permission);
        $this->default = (bool) $data["default"];
    }

    public function getDefault() : bool {
        return $this->default;
    }

    public function getValue() : ?bool {
        return $this->value;
    }

    public function getValueNonNull() : bool {
        if ($this->value !== null) {
            return $this->value;
        }
        return $this->default;
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!is_bool($value)) {
                throw new InvalidValueException("Expected value to be boolean or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }


    public function serializeValueType(mixed $data) : string {
        return $data ? "true" : "false";
    }

    public function unserializeValueType(string $serializedValue) : bool {
         if ($serializedValue === "true") return true;
         return false;
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["default"] = $this->serializeValueType($this->default);
        $data["value"] = $this->serializeValueType($this->value);
        return $data;
    }

    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->default = $this->unserializeValueType($data["default"]);
        $this->value = $this->unserializeValueType($data["value"]);
    }


    public function set(Plot $plot, Player $player, array $args) : bool {
        if ($this->ID !== self::FLAG_SERVER_PLOT) {
            $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
            if ($flag === null || $flag->getValueNonNull() === true) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
                return false;
            }
        }

        if (!isset($args[0])) {
            $player->sendMessage(
                ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.noValue", [$this->ID]));
            return false;
        }

        switch ($args[0]) {
            case "true":
                if ($this->value === true) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.valueSet", ["true", $this->ID]));
                    return false;
                }
                $this->value = true;
                break;

            case "false":
                if ($this->value === false) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.valueSet", ["false", $this->ID]));
                    return false;
                }
                $this->value = false;
                break;

            default:
                $player->sendMessage(
                    ResourceManager::getInstance()->getPrefix() .
                    ResourceManager::getInstance()->translateString(
                        "flag.set.invalidValue",
                        [$args[0], $this->ID, implode(ResourceManager::getInstance()->translateString("flag.set.invalidValue.validValue.separator"), ["true", "false"])]
                    )
                );
                return false;
        }

        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.success", [$this->ID, $args[0]]));
        return true;
    }

    public function remove(Plot $plot, Player $player, array $args) : bool {
        if ($this->ID !== self::FLAG_SERVER_PLOT) {
            $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
            if ($flag === null || $flag->getValueNonNull() === true) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
                return false;
            }
        }

        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
        $this->value = null;
        return true;
    }
}