<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\player\Player;

class ArrayFlag extends BaseFlag {

    protected array $default;
    protected ?array $value = null;

    public function __construct(string $ID, array $data, string $permission) {
        parent::__construct($ID, $data, $permission);
        $this->default = (array) $data["default"];
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
        $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
            return false;
        }

        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.noValue", [$this->ID]));
            return false;
        }

        $blockStringID = implode(" ", $args);
        $block = WorldSettings::parseBlock(["block" => $blockStringID], "block", VanillaBlocks::AIR());
        if ($block === null) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.invalidBlock", [$blockStringID]));
            return false;
        }
        $blockFullID = $block->getFullId();
        if ($this->value !== null) {
            if (array_search($blockFullID, $this->value) !== false) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.valueSet", [$block->getName(), $this->ID]));
                return false;
            }
        }

        if ($this->value === null) {
            $this->value = [];
        }
        $this->value[] = $block->getFullId();
        $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.success", [$this->ID, $block->getName()]));
        return true;
    }

    public function remove(Plot $plot, Player $player, array $args) : bool {
        $flag = $plot->getFlagNonNullByID(self::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.serverPlotFlag", [$flag->getID() ?? self::FLAG_SERVER_PLOT]));
            return false;
        }

        if ($this->value === null) return false;

        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
            $this->value = null;
            return true;
        } else {
            $blockStringID = implode(" ", $args);
            $block = WorldSettings::parseBlock(["block" => $blockStringID], "block", VanillaBlocks::AIR());
            if ($block === null) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.invalidBlock", [$blockStringID]));
                return false;
            }
            $key = array_search($block->getFullId(), $this->value);
            if ($key === false) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.valueNotExists", [$block->getName(), $this->ID]));
                return false;
            }
            unset($this->value[$key]);
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $block->getName()]));
        }
        return true;
    }
}