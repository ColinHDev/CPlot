<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags;

/**
 * @phpstan-template TValue
 */
interface Flag {

    public function getID() : string;

    /**
     * @return TValue
     */
    public function getValue() : mixed;

    /**
     * Create a new instance of the flag with the given value.
     * @phpstan-param TValue $value
     * @phpstan-return self<TValue>
     */
    public function createInstance(mixed $value) : self;
}