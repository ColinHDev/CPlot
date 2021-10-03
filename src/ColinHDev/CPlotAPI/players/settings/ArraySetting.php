<?php

namespace ColinHDev\CPlotAPI\players\settings;

use Closure;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\plots\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\players\Player as PlayerData;
use pocketmine\player\Player;

class ArraySetting extends BaseSetting {

    protected array $default;
    protected ?array $value = null;
    protected Closure $parseValue;

    public function __construct(string $ID, array $data, Closure $parseValue) {
        parent::__construct($ID, $data);
        $this->default = (array) $data["default"];
        $this->parseValue = $parseValue;
    }

    public function getDefault() : array {
        return $this->default;
    }

    public function getValue() : ?array {
        return $this->value;
    }

    public function getValueNonNull() : array {
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
            if (!is_array($value)) {
                throw new InvalidValueException("Expected value to be array or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }


    public function serializeValueType(mixed $data) : string {
        return implode(";", $data);
    }

    public function unserializeValueType(string $serializedValue) : array {
        if ($serializedValue === "") {
            $data = [];
        } else {
            $data = explode(";", $serializedValue);
        }
        return $data;
    }


    public function set(Player $player, PlayerData $playerData, array $args) : bool {
        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.noValue", [$this->ID]));
            return false;
        }

        $values = [];
        foreach ($args as $arg) {
            $value = ($this->parseValue)($arg);
            if ($value === null) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.invalidValue.noList", [$arg, $this->ID]));
                continue;
            }
            $values[] = $value;
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.set.success", [$this->ID, $arg]));
        }
        if (count($values) === 0) return false;

        if ($this->value === null) {
            $this->value = [];
        }
        $this->value = array_merge($this->value, $values);

        return true;
    }

    public function remove(Player $player, PlayerData $playerData, array $args) : bool {
        if ($this->value === null) return false;

        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
            $this->value = null;
            return true;

        } else {
            foreach ($args as $arg) {
                $value = ($this->parseValue)($arg);
                if ($value === null) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.remove.invalidValue.noList", [$arg, $this->ID]));
                    continue;
                }
                $key = array_search($value, $this->value);
                if ($key === false) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.remove.valueNotExists", [$arg, $this->ID]));
                    continue;
                }
                unset($this->value[$key]);
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("setting.remove.success", [$this->ID, $arg]));
            }
        }

        return true;
    }
}