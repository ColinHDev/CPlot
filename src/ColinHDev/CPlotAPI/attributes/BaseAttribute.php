<?php

namespace ColinHDev\CPlotAPI\attributes;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use ColinHDev\CPlotAPI\attributes\utils\AttributeTypeException;
use ColinHDev\CPlotAPI\players\settings\Setting;
use ColinHDev\CPlotAPI\plots\flags\Flag;

/**
 * @template AttributeType of BaseAttribute
 * @template AttributeValue
 */
abstract class BaseAttribute {

    protected static string $ID;
    protected static string $permission;
    protected static string $default;
    /** @var AttributeValue */
    protected mixed $value;

    /**
     * @param AttributeValue $value
     * @throws AttributeTypeException
     */
    public function __construct(mixed $value = null) {
        if ($value === null) {
            $this->value = $this->getParsedDefault();
        } else {
            $this->value = $value;
        }
    }

    public function getID() : string {
        return static::$ID;
    }

    public function getPermission() : string {
        return static::$permission;
    }

    /**
     * @throws AttributeTypeException
     */
    public function getDefault() : string {
        if (!isset(static::$default)) {
            $type = match (true) {
                $this instanceof Flag => "flag",
                $this instanceof Setting => "setting",
                default => throw new AttributeTypeException($this)
            };
            static::$default = ResourceManager::getInstance()->getConfig()->getNested($type . "." . static::$ID);
        }
        return static::$default;
    }

    /**
     * @return AttributeValue
     * @throws AttributeTypeException
     * @throws AttributeParseException
     */
    public function getParsedDefault() : mixed {
        if (!isset(static::$default)) {
            $type = match (true) {
                $this instanceof Flag => "flag",
                $this instanceof Setting => "setting",
                default => throw new AttributeTypeException($this)
            };
            static::$default = ResourceManager::getInstance()->getConfig()->getNested($type . "." . static::$ID);
        }
        return $this->parse(static::$default);
    }

    /**
     * @return AttributeValue
     */
    public function getValue() : mixed {
        return $this->value;
    }

    /**
     * @param AttributeValue | null $value
     * @return AttributeType
     * @throws AttributeTypeException
     */
    public function newInstance(mixed $value = null) : BaseAttribute {
        return new static($value);
    }

    /**
     * @param AttributeValue $value
     * @return AttributeType
     */
    abstract public function merge(mixed $value) : BaseAttribute;

    abstract public function toString(mixed $value = null) : string;

    /**
     * @return AttributeValue
     * @throws AttributeParseException
     */
    abstract public function parse(string $value) : mixed;

    /**
     * @throws AttributeTypeException
     */
    public function __serialize() : array {
        return [
            "ID" => $this->getID(),
            "permission" => $this->getPermission(),
            "default" => $this->getDefault(),
            "value" => $this->toString()
        ];
    }

    public function __unserialize(array $data) : void {
        static::$ID = $data["ID"];
        static::$permission = $data["permission"];
        static::$default = $data["default"];
        $this->value = $this->parse($data["value"]);
    }
}