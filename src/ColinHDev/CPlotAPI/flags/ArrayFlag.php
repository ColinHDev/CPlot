<?php

namespace ColinHDev\CPlotAPI\flags;

use ColinHDev\CPlotAPI\flags\utils\FlagParseException;

/**
 * @template TFlagType of ArrayFlag
 * @extends BaseFlag<TFlagType, array>
 */
abstract class ArrayFlag extends BaseFlag {

    protected array $value;

    /**
     * @throws FlagParseException
     */
    public function __construct(mixed $value = null) {
        if ($value === null) {
            $this->value = $this->getParsedDefault();
        } else {
            $this->value = $value;
        }
    }

    public function getValue() : array {
        return $this->value;
    }

    /**
     * @param array $value
     * @return TFlagType
     */
    public function merge(mixed $value) : ArrayFlag {
        return $this->flagOf(array_merge($this->value, $value));
    }

    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return json_encode($value);
    }

    public function parse(string $value) : array {
        return json_decode($value, true);
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