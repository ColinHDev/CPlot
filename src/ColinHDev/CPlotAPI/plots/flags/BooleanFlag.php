<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\plots\flags\utils\FlagParseException;

/**
 * @template TFlagType of BooleanFlag
 * @extends BaseFlag<TFlagType, bool>
 */
abstract class BooleanFlag extends BaseFlag {

    /** @var string[] */
    private const TRUE_VALUES = ["1", "yes", "allow", "true"];
    /** @var string[] */
    private const FALSE_VALUES = ["0", "no", "deny", "disallow", "false"];

    protected bool $value;

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

    public function getValue() : bool {
        return $this->value;
    }

    /**
     * @param bool $value
     * @return TFlagType
     */
    public function merge(mixed $value) : BooleanFlag {
        return $this->flagOf($value);
    }

    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return $value ? "true" : "false";
    }

    /**
     * @throws FlagParseException
     */
    public function parse(string $value) : bool {
        $value = strtolower($value);
        if (array_search($value, self::TRUE_VALUES, true) !== false) {
            return true;
        }
        if (array_search($value, self::FALSE_VALUES, true) !== false) {
            return false;
        }
        throw new FlagParseException($this, $value);
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