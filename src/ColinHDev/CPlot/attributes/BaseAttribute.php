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

    /**
     * Returns the ID of the attribute.
     */
    public function getID() : string {
        return $this->ID;
    }

    /**
     * Returns the value of the attribute.
     * @return TValue
     */
    public function getValue() : mixed {
        return $this->value;
    }

    /**
     * Checks if the given attribute is the same as this one and if so, checks if both share the same value.
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
     * Merges this attributes's value with another value and return an instance holding the merged value.
     *
     * @param TValue $value
     * @return self<TValue>
     */
    abstract public function merge(mixed $value) : self;

    /**
     * Returns an example of a string that would parse into a valid value of this instance.
     */
    abstract public function getExample() : string;

    /**
     * Returns a string representation of the instance, that when passed through {@see parse()} will result in
     * an equivalent instance.
     *
     * @return string representation of the attribute
     */
    abstract public function toString() : string;

    /**
     * Returns a more easily readable string representation of the instance, that might not be parseable with
     * {@see parse()}.
     * This method is used for display purposes and should not be used for storage or parsing.
     *
     * @return string representation of the attribute
     */
    abstract public function toReadableString() : string;

    /**
     * Parse a string into a attribute value, and throw an exception in the case that the string does not represent a
     * valid value.
     * Returns the parsed value.
     *
     * @return TValue
     * @throws AttributeParseException if the value could not be parsed
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