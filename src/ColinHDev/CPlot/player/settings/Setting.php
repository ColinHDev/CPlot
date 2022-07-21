<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template TValue of mixed
 */
interface Setting {

    public function getID() : string;

    /**
     * @return TValue
     */
    public function getValue() : mixed;

    /**
     * @phpstan-param BaseAttribute<mixed>&Setting<mixed> $other
     */
    public function equals(BaseAttribute $other) : bool;

    /**
     * Check if the given value is equal or part of the attribute's value.
     * @phpstan-param TValue $value
     */
    public function contains(mixed $value) : bool;

    /**
     * Create a new instance of the flag with the given value.
     * @phpstan-param TValue $value
     * @phpstan-return BaseAttribute<TValue>&Setting<TValue>
     */
    public function createInstance(mixed $value) : BaseAttribute;

    /**
     * @phpstan-param TValue $value
     * @phpstan-return BaseAttribute<TValue>&Setting<TValue>
     */
    public function merge(mixed $value) : BaseAttribute;

    /**
     * @phpstan-param TValue $value
     */
    public function toString(mixed $value = null) : string;

    /**
     * @phpstan-return TValue
     * @throws AttributeParseException
     */
    public function parse(string $value) : mixed;
}