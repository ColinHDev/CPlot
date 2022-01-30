<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes\utils;

use ColinHDev\CPlot\attributes\BaseAttribute;

class AttributeParseException extends \Exception {

    private BaseAttribute $attribute;
    private string $value;

    public function __construct(BaseAttribute $attribute, string $value) {
        parent::__construct("Failed to parse attribute " . $attribute::class . ". Value " . $value . " was not accepted.");
        $this->attribute = $attribute;
        $this->value = $value;
    }

    public function getAttribute() : BaseAttribute {
        return $this->attribute;
    }

    public function getValue() : string {
        return $this->value;
    }
}