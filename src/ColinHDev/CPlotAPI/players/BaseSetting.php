<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlotAPI\players\Player as PlayerData;
use pocketmine\player\Player;

abstract class BaseSetting implements SettingIDs {

    protected string $ID;
    protected string $category;
    protected string $valueType;
    protected string $description;

    public function __construct(string $ID, array $data) {
        $this->ID = $ID;
        $this->category = $data["category"];
        $this->valueType = $data["type"];
        $this->description = $data["description"];
    }

    public function getID() : string {
        return $this->ID;
    }

    public function getCategory() : string {
        return $this->category;
    }

    public function getValueType() : string {
        return $this->valueType;
    }

    public function getDescription() : string {
        return $this->description;
    }

    abstract public function getDefault() : mixed;

    abstract public function getValue() : mixed;
    abstract public function getValueNonNull() : mixed;
    abstract public function setValue(mixed $value) : void;

    abstract public function serializeValueType(mixed $data) : string;
    abstract public function unserializeValueType(string $serializedValue) : mixed;

    abstract public function set(Player $player, PlayerData $playerData, array $args) : bool;
    abstract public function remove(Player $player, PlayerData $playerData, array $args) : bool;
}