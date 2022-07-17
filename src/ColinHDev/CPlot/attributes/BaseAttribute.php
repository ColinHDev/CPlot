<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use InvalidArgumentException;
use function is_string;

/**
 * @phpstan-template AttributeValue
 */
abstract class BaseAttribute {

    protected string $ID;
    /** @phpstan-var AttributeValue */
    protected mixed $value;

    /**
     * @phpstan-param AttributeValue $value
     */
    public function __construct(string $ID, mixed $value) {
        $this->ID = $ID;
        $this->value = $value;
    }

    public function getID() : string {
        return $this->ID;
    }

    /**
     * @return AttributeValue
     */
    public function getValue() : mixed {
        return $this->value;
    }

    //abstract public function equals(BaseAttribute $other) : bool;

    /**
     * Create a new instance of the attribute with the given value.
     * @phpstan-param AttributeValue $value
     * @phpstan-return self
     */
    abstract public function createInstance(mixed $value) : self;

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
     * @phpstan-return array{ID: string, value: string}
     */
    public function __serialize() : array {
        return [
            "ID" => $this->ID,
            "value" => $this->toString()
        ];
    }

    /**
     * @phpstan-param array<mixed, mixed> $data
     * @throws InvalidArgumentException
     */
    public function __unserialize(array $data) : void {
        if (isset($data["ID"], $data["value"]) && is_string($data["ID"]) && is_string($data["value"])) {
            $this->ID = $data["ID"];
            try {
                $this->value = $this->parse($data["value"]);
                return;
            } catch(AttributeParseException) {
            }
        }
        throw new InvalidArgumentException("Invalid serialized data given for " . static::class);
    }
}