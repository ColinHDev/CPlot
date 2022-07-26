<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\attributes;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\plots\flags\InternalFlag;
use JsonException;
use function count;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

/**
 * @phpstan-extends ListAttribute<array<Flag<mixed>>>
 */
abstract class FlagListAttribute extends ListAttribute {

    public function equals(object $other) : bool {
        if (!($other instanceof static)) {
            return false;
        }
        /** @var array<Flag<mixed>> $otherValue */
        $otherValue = $other->getValue();
        if (count($this->value) !== count($otherValue)) {
            return false;
        }
        /** @var Flag<mixed> $flag */
        foreach ($this->value as $i => $flag) {
            if (!isset($otherValue[$i])) {
                return false;
            }
            $otherFlag = $otherValue[$i];
            if (!$flag->equals($otherFlag)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Flag<mixed> $value
     */
    public function contains(mixed $value) : bool {
        /** @var Flag<mixed> $currentValue */
        foreach ($this->value as $currentValue) {
            if ($currentValue->equals($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<Flag<mixed>> | null $value
     * @throws JsonException
     */
    public function toString(mixed $value = null) : string {
        if ($value === null) {
            $value = $this->value;
        }
        $flags = [];
        foreach ($value as $flag) {
            $flags[] = $flag->getID() . "=" . $flag->toString();
        }
        return json_encode($flags, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<Flag<mixed>>
     * @throws AttributeParseException
     */
    public function parse(string $value) : array {
        $flagParts = explode("=", $value);
        if (count($flagParts) === 2) {
            try {
                $flag = $this->parseFlagFromString($value);
                if ($flag instanceof Flag) {
                    return [$flag];
                }
            } catch(AttributeParseException) {
            }
        } else if (count($flagParts) > 2) {
            try {
                $flags = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($flags)) {
                    $parsedFlags = [];
                    foreach ($flags as $flagParts) {
                        try {
                            $flag = $this->parseFlagFromString($flagParts);
                            if ($flag instanceof Flag) {
                                $parsedFlags[] = $flag;
                            }
                        } catch(AttributeParseException) {
                            throw new AttributeParseException($this, $value);
                        }
                    }
                    return $parsedFlags;
                }
            } catch(JsonException) {
            }
            if (str_contains($value, ",")) {
                if (str_contains($value, ", ")) {
                    $flags = explode(", ", $value);
                } else {
                    $flags = explode(",", $value);
                }
                $parsedFlags = [];
                foreach ($flags as $flagParts) {
                    try {
                        $flag = $this->parseFlagFromString($flagParts);
                        if ($flag instanceof Flag) {
                            $parsedFlags[] = $flag;
                        }
                    } catch(AttributeParseException) {
                        throw new AttributeParseException($this, $value);
                    }
                }
                if (count($parsedFlags) > 0) {
                    return $parsedFlags;
                }
            }
        }
        throw new AttributeParseException($this, $value);
    }

    /**
     * @return Flag<mixed>|null
     * @throws AttributeParseException
     */
    private function parseFlagFromString(string $flagString) : ?Flag {
        /**
         * @var string|null $flagID
         * @var string|null $flagStringValue
         */
        [$flagID, $flagStringValue] = explode("=", $flagString);
        if (!is_string($flagID) || !is_string($flagStringValue)) {
            return null;
        }
        $flag = FlagManager::getInstance()->getFlagByID($flagID);
        if ($flag instanceof Flag && !($flag instanceof InternalFlag)) {
            try {
                $flagValue = $flag->parse($flagStringValue);
            } catch(AttributeParseException) {
                throw new AttributeParseException($this, $flagString);
            }
            return $flag->createInstance($flagValue);
        }
        return null;
    }
}