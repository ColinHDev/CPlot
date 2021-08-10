<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\Plot;
use pocketmine\player\Player;

abstract class BaseFlag implements FlagIDs {

    protected string $ID;
    protected string $category;
    protected string $valueType;
    protected string $description;
    protected string $permission;

    public function __construct(string $ID, array $data, string $permission) {
        $this->ID = $ID;
        $this->category = $data["category"];
        $this->valueType = $data["type"];
        $this->description = $data["description"];
        $this->permission = $permission;
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

    public function getPermission() : string {
        return $this->permission;
    }

    abstract public function getDefault() : mixed;

    abstract public function getValue() : mixed;
    abstract public function getValueNonNull() : mixed;
    abstract public function setValue(mixed $value) : void;

    abstract public function serializeValueType(mixed $data) : string;
    abstract public function unserializeValueType(string $serializedValue) : mixed;

    abstract public function set(Plot $plot, Player $player, array $args) : bool;
    abstract public function remove(Plot $plot, Player $player, array $args) : bool;

    public function __serialize() : array {
        return [
            "ID" => $this->ID,
            "category" => $this->category,
            "valueType" => $this->valueType,
            "description" => $this->description,
        ];
    }

    public function __unserialize(array $data) : void {
        $this->ID = $data["ID"];
        $this->category = $data["category"];
        $this->valueType = $data["valueType"];
        $this->description = $data["description"];
    }
}