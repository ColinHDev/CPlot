<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use InvalidArgumentException;
use function is_string;

/**
 * @template TValue of mixed
 */
abstract class BaseAttribute {

    protected string $ID;
    /** @var TValue */
    protected mixed $value;

    /**
     * @param TValue $value
     */
    public function __construct(string $ID, mixed $value) {
        $this->ID = $ID;
        $this->value = $value;
    }

    public function getID() : string {
        return $this->ID;
    }

    /**
     * @return TValue
     */
    public function getValue() : mixed {
        return $this->value;
    }

    /**
     * @param static<TValue> $other
     */
    abstract public function equals(object $other) : bool;

    /**
     * Check if the given value is equal or part of the attribute's value.
     * @param (TValue is array ? value-of<TValue> : TValue) $value
     */
    abstract public function contains(mixed $value) : bool;

    /**
     * Create a new instance of the attribute with the given value.
     * @param TValue $value
     */
    abstract public function createInstance(mixed $value) : static;

    /**
     * @param TValue $value
     * @return self<TValue>
     */
    abstract public function merge(mixed $value) : self;

    /**
     * @param TValue $value
     */
    abstract public function toString(mixed $value = null) : string;

    /**
     * @return TValue
     * @throws AttributeParseException
     */
    abstract public function parse(string $value) : mixed;

    /**
     * @return array{ID: string, value: string}
     */
    public function __serialize() : array {
        return [
            "ID" => $this->ID,
            "value" => $this->toString()
        ];
    }

    /**
     * @param array<array-key, mixed> $data
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