<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\FlagParseException;

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
    protected static string $default;

    public static function init(string $ID, string $category, string $type, string $description, string $permission, string $default) {
        self::$ID = $ID;
        self::$category = $category;
        self::$type = $type;
        self::$description = $description;
        self::$permission = $permission;
        self::$default = $default;
    }

    /**
     * @param TFlagValue $value
     */
    abstract public function __construct(mixed $value);

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
     * @return TFlagValue
     * @throws FlagParseException
     */
    public function getDefault() : mixed {
        return $this->parse(self::$default);
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

    abstract public function toString(mixed $value = null) : string;

    /**
     * @return TFlagValue
     * @throws FlagParseException
     */
    abstract public function parse(string $value) : mixed;

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
        self::$default = $data["default"];
    }
}