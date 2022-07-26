<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes\utils;

use ColinHDev\CPlot\attributes\BaseAttribute;
use Exception;

class AttributeParseException extends Exception {

    /** @var BaseAttribute<mixed> */
    private BaseAttribute $attribute;
    private string $value;

    /**
     * @param BaseAttribute<mixed> $attribute
     */
    public function __construct(BaseAttribute $attribute, string $value) {
        parent::__construct("Failed to parse attribute " . $attribute::class . ". Value " . $value . " was not accepted.");
        $this->attribute = $attribute;
        $this->value = $value;
    }

    /**
     * @return BaseAttribute<mixed>
     */
    public function getAttribute() : BaseAttribute {
        return $this->attribute;
    }

    public function getValue() : string {
        return $this->value;
    }
}