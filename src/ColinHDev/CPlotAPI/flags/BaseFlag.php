<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\Plot;
use pocketmine\player\Player;

/**
 * @template TFlagType of BaseFlag
 * @template TFlagValue
 */
abstract class BaseFlag implements FlagIDs {

    protected static string $ID;
    protected static string $category;
    protected static string $type;
    protected static string $description;
    protected static string $permission;
    /** @var TFlagValue | null */
    protected static mixed $default;

    /**
     * @param TFlagValue | null $default
     */
    public static function init(string $ID, string $category, string $type, string $description, string $permission, mixed $default) {
        self::$ID = $ID;
        self::$category = $category;
        self::$type = $type;
        self::$description = $description;
        self::$permission = $permission;
        self::$default = $default;
    }

    /**
     * @param TFlagValue | null $value
     */
    abstract public function __construct(mixed $value = null);

    public function getID() : string {
        return self::$ID;
    }

    public function getCategory() : string {
        return self::$category;
    }

    public function getType() : string {
        return self::$type;
    }

    public function getDescription() : string {
        return self::$description;
    }

    public function getPermission() : string {
        return self::$permission;
    }

    /**
     * @return TFlagValue | null
     */
    public function getDefault() : mixed {
        return self::$default;
    }

    /**
     * @return TFlagValue | null
     */
    abstract public function getValue() : mixed;

    /**
     * @param TFlagValue $value
     * @return TFlagType
     */
    abstract public function merge(mixed $value) : BaseFlag;

    /**
     * @param TFlagValue $value
     * @return TFlagType
     */
    abstract public function flagOf(mixed $value) : BaseFlag;

    abstract public function toString() : string;

    /**
     * @return TFlagType
     */
    abstract public static function parse(string $value) : BaseFlag;

    public function __serialize() : array {
        return [
            "ID" => self::$ID,
            "category" => self::$category,
            "type" => self::$type,
            "description" => self::$description,
            "default" => self::$default
        ];
    }

    public function __unserialize(array $data) : void {
        self::$ID = $data["ID"];
        self::$category = $data["category"];
        self::$type = $data["type"];
        self::$description = $data["description"];
    }
}