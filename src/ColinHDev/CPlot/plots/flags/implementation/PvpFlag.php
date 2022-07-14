<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\flags\implementation;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\FlagIDs;

class PvpFlag extends BooleanAttribute {

    public function __construct(bool $value) {
        parent::__construct(FlagIDs::FLAG_PVP, $value);
    }

    public static function TRUE() : self {
        return new self(true);
    }

    public static function FALSE() : self {
        return new self(false);
    }
}