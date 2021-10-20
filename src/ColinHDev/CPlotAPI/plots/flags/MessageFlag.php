<?php

namespace ColinHDev\CPlotAPI\plots\flags;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;

/**
 * @extends BaseAttribute<MessageFlag, string>
 */
class MessageFlag extends BaseAttribute implements Flag {

    protected static string $ID = self::FLAG_MESSAGE;
    protected static string $permission = self::PERMISSION_BASE . self::FLAG_MESSAGE;
    protected static string $default;

    /**
     * @param string $value
     */
    public function merge(mixed $value) : MessageFlag {
        return new static($value);
    }

    /**
     * @param string | null $value
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        return $value;
    }

    public function parse(string $value) : string {
        return $value;
    }
}