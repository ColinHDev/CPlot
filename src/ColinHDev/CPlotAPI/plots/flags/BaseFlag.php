<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\plots\flags\utils\FlagParseException;

/**
 * @template TFlagType of BaseFlag
 * @template TFlagValue
 */
abstract class BaseFlag implements FlagIDs {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;

    public static function init(string $ID, string $permission, string $default) {
        self::$ID = $ID;
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

    public function getPermission() : string {
        return self::$permission;
    }

    public function getDefault() : string {
        return self::$default;
    }

    /**
     * @return TFlagValue
     * @throws FlagParseException
     */
    public function getParsedDefault() : mixed {
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
            "permission" => self::$permission,
            "default" => self::$default
        ];
    }

    public function __unserialize(array $data) : void {
        self::$ID = $data["ID"];
        self::$permission = $data["permission"];
        self::$default = $data["default"];
    }
}