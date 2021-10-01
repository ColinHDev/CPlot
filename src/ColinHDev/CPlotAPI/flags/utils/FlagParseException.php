<?php

namespace ColinHDev\CPlotAPI\flags\utils;

use ColinHDev\CPlotAPI\flags\BaseFlag;

class FlagParseException extends \Exception {

    private BaseFlag $flag;
    private string $value;

    public function __construct(BaseFlag $flag, string $value) {
        parent::__construct("Failed to parse flag of type " . $flag->getID() . ". Value " . $value . " was not accepted.");
        $this->flag = $flag;
        $this->value = $value;
    }

    public function getFlag() : BaseFlag {
        return $this->flag;
    }

    public function getValue() : string {
        return $this->value;
    }
}