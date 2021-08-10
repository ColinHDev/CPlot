<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\player\Player;

class StringFlag extends BaseFlag {

    protected string $default;
    protected ?string $value = null;

    /**
     * StringFlag constructor.
     * @param string    $ID
     * @param array     $data
     * @param string    $permission
     */
    public function __construct(string $ID, array $data, string $permission) {
        parent::__construct($ID, $data, $permission);
        $this->default = (string) $data["standard"];
    }

    /**
     * @return string
     */
    public function getDefault() : string {
        return $this->default;
    }

    /**
     * @return string | null
     */
    public function getValue() : ?string {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getValueNonNull() : string {
        if ($this->value !== null) {
            return $this->value;
        }
        return $this->default;
    }

    /**
     * @param mixed $value
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!is_string($value)) {
                throw new InvalidValueException("Expected value to be string or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return $data;
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
        return $serializedValue;
    }


    /**
     * @param Plot      $plot
     * @param Player    $player
     * @param array     $args
     * @return bool
     */
    public function set(Plot $plot, Player $player, array $args) : bool {
        $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
            return false;
        }

        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.noValue", [$this->ID]));
            return false;
        }

        $this->value = implode(" ", $args);
        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.success", [$this->ID, $this->serializeValueType($this->value)]));
        return true;
    }

    /**
     * @param Plot      $plot
     * @param Player    $player
     * @param array     $args
     * @return bool
     */
    public function remove(Plot $plot, Player $player, array $args) : bool {
        $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
            return false;
        }

        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
        $this->value = null;
        return true;
    }


    /**
     * @return array
     */
    public function __serialize() : array {
        $data = parent::__serialize();
        $data["default"] = $this->serializeValueType($this->default);
        $data["value"] = $this->serializeValueType($this->value);
        return $data;
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->default = $this->unserializeValueType($data["default"]);
        $this->value = $this->unserializeValueType($data["value"]);
    }
}