<?php

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @template AttributeValue
 */
abstract class BaseAttribute {

    protected string $ID;
    protected string $permission;
    protected string $default;
    /** @var AttributeValue */
    protected mixed $value;

    /**
     * @param AttributeValue $value
     * @throws AttributeParseException
     */
    final public function __construct(string $ID, string $permission, string $default, mixed $value = null) {
        $this->ID = $ID;
        $this->permission = $permission;
        $this->default = $default;
        if ($value === null) {
            $this->value = $this->getParsedDefault();
        } else {
            $this->value = $value;
        }
    }

    public function getID() : string {
        return $this->ID;
    }

    public function getPermission() : string {
        return $this->permission;
    }

    public function getDefault() : string {
        return $this->default;
    }

    /**
     * @return AttributeValue
     * @throws AttributeParseException
     */
    public function getParsedDefault() : mixed {
        return $this->parse($this->default);
    }

    /**
     * @return AttributeValue
     */
    public function getValue() : mixed {
        return $this->value;
    }

    /**
     * @param AttributeValue $value
     * @return static
     */
    public function newInstance(mixed $value) : static {
        return new static($this->ID, $this->permission, $this->default, $value);
    }

    /**
     * @param AttributeValue $value
     * @return BaseAttribute
     */
    abstract public function merge(mixed $value) : BaseAttribute;

    /**
     * @param AttributeValue $value
     */
    abstract public function toString(mixed $value = null) : string;

    /**
     * @return AttributeValue
     * @throws AttributeParseException
     */
    abstract public function parse(string $value) : mixed;

    public function __serialize() : array {
        return [
            "ID" => $this->ID,
            "permission" => $this->permission,
            "default" => $this->default,
            "value" => $this->toString()
        ];
    }

    /**
     * @throws AttributeParseException
     */
    public function __unserialize(array $data) : void {
        $this->ID = $data["ID"];
        $this->permission = $data["permission"];
        $this->default = $data["default"];
        $this->value = $this->parse($data["value"]);
    }
}