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
        static::$ID = $ID;
        static::$permission = $permission;
        static::$default = $default;
    }

    /**
     * @param TFlagValue $value
     */
    abstract public function __construct(mixed $value);

    public function getID() : string {
        return static::$ID;
    }

    public function getPermission() : string {
        return static::$permission;
    }

    public function getDefault() : string {
        return static::$default;
    }

    /**
     * @return TFlagValue
     * @throws FlagParseException
     */
    public function getParsedDefault() : mixed {
        return $this->parse(static::$default);
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
            "ID" => static::$ID,
            "permission" => static::$permission,
            "default" => static::$default
        ];
    }

    public function __unserialize(array $data) : void {
        static::$ID = $data["ID"];
        static::$permission = $data["permission"];
        static::$default = $data["default"];
    }
}