<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\utils\InvalidValueException;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\player\Player;

class ArrayFlag extends BaseFlag {

    protected array $default;
    protected ?array $value = null;

    /**
     * ArrayFlag constructor.
     * @param string    $ID
     * @param array     $data
     * @param string    $permission
     */
    public function __construct(string $ID, array $data, string $permission) {
        parent::__construct($ID, $data, $permission);
        $this->default = (array) $data["standard"];
    }

    /**
     * @return array
     */
    public function getDefault() : array {
        return $this->default;
    }

    /**
     * @return array | null
     */
    public function getValue() : ?array {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getValueNonNull() : array {
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
            if (!is_array($value)) {
                throw new InvalidValueException("Expected value to be array or null, got " . gettype($value) . ".");
            }
        }
        $this->value = $value;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public function serializeValueType(mixed $data) : string {
        return implode(";", $data);
    }

    /**
     * @param string $serializedValue
     * @return mixed
     */
    public function unserializeValueType(string $serializedValue) : mixed {
        if ($serializedValue === "") {
            $data = [];
        } else {
            $data = explode(";", $serializedValue);
        }
        return $data;
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

        $blocks = [];
        foreach ($args as $arg) {
            try {
                $block = LegacyStringToItemParser::getInstance()->parse($arg)->getBlock();
            } catch (LegacyStringToItemParserException $exception) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.parseBlockError", [$arg, $exception->getMessage()]));
                continue;
            }
            $blockFullID = $block->getFullId();
            if ($this->value !== null) {
                if (array_search($blockFullID, $this->value) !== false) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.valueSet", [$block->getName(), $this->ID]));
                    continue;
                }
            }
            if (array_search($blockFullID, $blocks) !== false) {
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.valueSet", [$block->getName(), $this->ID]));
                continue;
            }
            $blocks[] = $blockFullID;
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.success", [$this->ID, $block->getName()]));
        }
        if (count($blocks) === 0) return false;

        if ($this->value === null) {
            $this->value = [];
        }
        $this->value = array_merge($this->value, $blocks);

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

        if ($this->value === null) return false;

        if (count($args) < 1) {
            $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $this->serializeValueType($this->value)]));
            $this->value = null;
            return true;
        } else {
            foreach ($args as $arg) {
                try {
                    $block = LegacyStringToItemParser::getInstance()->parse($args[0])->getBlock();
                } catch (LegacyStringToItemParserException $exception) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.set.parseBlockError", [$arg, $exception->getMessage()]));
                    continue;
                }
                $key = array_search($block->getFullId(), $this->value);
                if ($key === false) {
                    $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.valueNotExists", [$block->getName(), $this->ID]));
                    continue;
                }
                unset($this->value[$key]);
                $player->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("flag.remove.success", [$this->ID, $block->getName()]));
            }
        }

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