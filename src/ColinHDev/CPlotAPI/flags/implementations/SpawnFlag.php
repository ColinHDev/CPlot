<?php

namespace ColinHDev\CPlotAPI\flags\implementations;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\BaseFlag;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\entity\Location;
use pocketmine\player\Player;

class SpawnFlag extends BaseFlag {

    protected ?Location $value = null;

    public function getDefault() : mixed {
        return null;
    }

    public function getValue() : ?Location {
        return $this->value;
    }

    public function getValueNonNull() : ?Location {
        return $this->getValue();
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(mixed $value) : void {
        if ($value !== null) {
            if (!$value instanceof Location) {
                throw new InvalidValueException("Expected value to be instance of Location or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }


    public function serializeValueType(mixed $data) : string {
        if (!$data instanceof Location) return "null";
        return $data->getX() . ";" . $data->getY() . ";" . $data->getZ() . ";" . $data->getYaw() . ";" . $data->getPitch();
    }

    public function unserializeValueType(string $serializedValue) : ?Location {
        if ($serializedValue === "null") return null;
        [$x, $y, $z, $yaw, $pitch] = explode(";", $serializedValue);
        return new Location((float) $x, (float) $y, (float) $z, (float) $yaw, (float) $pitch);
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

        $location = $player->getLocation();
        $this->value = new Location(
            round($location->x, 1),
            round($location->y, 1),
            round($location->z, 1),
            round($location->yaw, 3),
            round($location->pitch, 3)
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