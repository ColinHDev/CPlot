<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @template TValue of mixed
 */
interface Setting {

    public function getID() : string;

    /**
     * @return TValue
     */
    public function getValue() : mixed;

    /**
     * @param static<mixed> $other
     */
    public function equals(object $other) : bool;

    /**
     * Check if the given value is equal or part of the attribute's value.
     * @param (TValue is array ? value-of<TValue> : TValue) $value
     */
    public function contains(mixed $value) : bool;

    /**
     * Create a new instance of the flag with the given value.
     * @param TValue $value
     * @return static
     */
    public function createInstance(mixed $value) : static;

    /**
     * @param TValue $value
     * @return static<TValue>
     */
    public function merge(mixed $value) : object;

    /**
     * @param TValue $value
     */
    public function toString(mixed $value = null) : string;

    /**
     * @return TValue
     * @throws AttributeParseException
     */
    public function parse(string $value) : mixed;
}