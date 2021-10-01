<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\FlagParseException;

/**
 * @template TFlagType of StringFlag
 * @extends BaseFlag<TFlagType, string>
 */
abstract class StringFlag extends BaseFlag {

    protected string $value;

    public function __construct(mixed $value) {
        $this->value = $value;
    }

    public function getValue() : string {
        return $this->value;
    }

    /**
     * @param string $value
     * @return TFlagType
     */
    public function merge(mixed $value) : StringFlag {
        return $this->flagOf($this->value . " " . $value);
    }

    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return $value;
    }

    public function parse(string $value) : string {
        return $value;
    }

    public function __serialize() : array {
        $data = parent::__serialize();
        $data["value"] = $this->toString();
        return $data;
    }

    /**
     * @throws FlagParseException
     */
    public function __unserialize(array $data) : void {
        parent::__unserialize($data);
        $this->value = $this->parse($data["value"]);
    }
}