<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\utils\AttributeParseException;

/**
 * @phpstan-template TFlagType of Flag&BaseAttribute
 * @phpstan-template TFlagValue of mixed
 */
interface Flag {

    public function getID() : string;

    /**
     * @return TFlagValue
     */
    public function getValue() : mixed;

    /**
     * @phpstan-param TFlagType $other
     */
    public function equals(BaseAttribute $other) : bool;

    /**
     * Create a new instance of the flag with the given value.
     * @phpstan-param TFlagValue $value
     * @phpstan-return TFlagType<TFlagValue>
     */
    public function createInstance(mixed $value) : BaseAttribute;

    /**
     * @phpstan-param TFlagValue $value
     * @phpstan-return TFlagType<TFlagValue>
     */
    public function merge(mixed $value) : BaseAttribute;

    /**
     * @phpstan-param TFlagValue $value
     */
    public function toString(mixed $value = null) : string;

    /**
     * @phpstan-return TFlagValue
     * @throws AttributeParseException
     */
    public function parse(string $value) : mixed;
}