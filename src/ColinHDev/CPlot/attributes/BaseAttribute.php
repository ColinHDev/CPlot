<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template AttributeValue
 */
abstract class BaseAttribute {

    protected string $ID;
    protected string $permission;
    /** @phpstan-var AttributeValue */
    protected mixed $value;

    /**
     * @phpstan-param AttributeValue $value
     */
    final public function __construct(string $ID, string $permission, mixed $value) {
        $this->ID = $ID;
        $this->permission = $permission;
        $this->value = $value;
    }

    public function getID() : string {
        return $this->ID;
    }

    public function getPermission() : string {
        return $this->permission;
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
     * @return BaseAttribute<AttributeValue>
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

    /**
     * @phpstan-return array{ID: string, permission: string, default: string, value: string}
     */
    public function __serialize() : array {
        return [
            "ID" => $this->ID,
            "permission" => $this->permission,
            "default" => $this->default,
            "value" => $this->toString()
        ];
    }

    /**
     * @phpstan-param array{ID: string, permission: string, default: string, value: string} $data
     * @throws AttributeParseException
     */
    public function __unserialize(array $data) : void {
        $this->ID = $data["ID"];
        $this->permission = $data["permission"];
        $this->default = $data["default"];
        $this->value = $this->parse($data["value"]);
    }
}