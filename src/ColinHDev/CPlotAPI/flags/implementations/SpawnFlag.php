<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class SpawnFlag extends BaseFlag {

    protected ?Vector3 $value = null;

    public function getDefault() : mixed {
        return null;
    }

    public function getValue() : ?Vector3 {
        return $this->value;
    }

    public function getValueNonNull() : ?Vector3 {
        return $this->getValue();
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!$value instanceof Vector3) {
                throw new InvalidValueException("Expected value to be instance of Vector3 or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }


    public function serializeValueType(mixed $data) : string {
        if ($data === null) return "null";
        return $data->getX() . ";" . $data->getY() . ";" . $data->getZ();
    }

    public function unserializeValueType(string $serializedValue) : ?Vector3 {
        if ($serializedValue === "null") return null;
        [$x, $y, $z] = explode(";", $serializedValue);
        return new Vector3((float) $x, (float) $y, (float) $z);
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["value"] = $this->serializeValueType($this->value);
        return $data;
    }

    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->value = $this->unserializeValueType($data["value"]);
    }


    public function set(Plot $plot, Player $player, array $args) : bool {
        $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
            return false;
        }

        $position = $player->getPosition()->asVector3();
        $this->value = new Vector3(
            round($position->x, 1),
            round($position->y, 1),
            round($position->z, 1)
        );
        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.success", [$this->ID, $this->serializeValueType($this->value)]));
        return true;
    }

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
}