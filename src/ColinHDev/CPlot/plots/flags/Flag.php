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

}