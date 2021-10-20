<?php

namespace ColinHDev\CPlotAPI\attributes\utils;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\players\settings\Setting;
use ColinHDev\CPlotAPI\plots\flags\Flag;

class AttributeTypeException extends \Exception {

    private BaseAttribute $attribute;

    public function __construct(BaseAttribute $attribute) {
        parent::__construct("Attribute " . $attribute::class . " is neither of type " . Flag::class . " or " . Setting::class . ".");
        $this->attribute = $attribute;
    }

    public function getAttribute() : BaseAttribute {
        return $this->attribute;
    }
}