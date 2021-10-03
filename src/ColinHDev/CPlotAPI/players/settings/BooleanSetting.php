<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\plots\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\players\Player as PlayerData;
use pocketmine\player\Player;

class BooleanSetting extends BaseSetting {

    protected bool $default;
    protected ?bool $value = null;

    public function __construct(string $ID, array $data) {
        parent::__construct($ID, $data);
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


    public function set(Player $player, PlayerData $playerData, array $args) : bool {
        if (!isset($args[0])) {
            $player->sendMessage(
                ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.noValue", [$this->ID]));
            return false;
        }

        switch ($args[0]) {
            case "true":
                if ($this->value === true) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.valueSet", ["true", $this->ID]));
                    return false;
                }
                $this->value = true;
                break;

            case "false":
                if ($this->value === false) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.valueSet", ["false", $this->ID]));
                    return false;
                }
                $this->value = false;
                break;

            default:
                $player->sendMessage(
                    ResourceManager::getInstance()->getPrefix() .
                    ResourceManager::getInstance()->translateString(
                        "setting.set.invalidValue",
                        [$args[0], $this->ID, implode(ResourceManager::getInstance()->translateString("setting.set.invalidValue.validValue.separator"), ["true", "false"])]
                    )
                );
                return false;
        }

        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.success", [$this->ID, $args[0]]));
        return true;
    }

    public function remove(Player $player, PlayerData $playerData, array $args) : bool {
        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
        $this->value = null;
        return true;
    }
}