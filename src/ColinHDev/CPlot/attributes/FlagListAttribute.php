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
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
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
            $flagValue = $value->getValue();
            if (is_array($flagValue)) {
                foreach($flagValue as $flag) {
                    if ($currentValue->contains($flag)) {
                        return true;
                    }
                }
            } else if ($currentValue->contains($flagValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<Flag<mixed>> $value
     */
    public function merge(mixed $value) : self {
        /** @var array<Flag<mixed>> $values */
        $values = $this->value;
        /** @var Flag<mixed> $newFlag */
        foreach ($value as $newFlag) {
            /** @var Flag<mixed> $oldFlag */
            foreach($values as $i => $oldFlag) {
                if (!($oldFlag instanceof $newFlag)) {
                    continue;
                }
                if (is_array($oldFlag->getValue()) && is_array($newFlag->getValue())) {
                    $values[$i] = $oldFlag->merge($newFlag->getValue());
                    continue 2;
                }
                if ($oldFlag->equals($newFlag)) {
                    continue 2;
                }
            }
            $values[] = $newFlag;
        }
        return $this->createInstance($values);
    }

    public function getExample() : string {
        return "pvp=true, item_pickup=false, use=tnt";
    }

    /**
     * @throws JsonException
     */
    public function toString() : string {
        $flags = [];
        foreach ($this->value as $flag) {
            $flags[] = $flag->getID() . "=" . $flag->toString();
        }
        return json_encode($flags, JSON_THROW_ON_ERROR);
    }

    public function toReadableString() : string {
        return implode(", ",
            array_map(
                static function(Flag $flag) : string {
                    return $flag->getID() . "=" . $flag->toReadableString();
                },
                $this->value
            )
        );
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
        $flagParts = explode("=", $flagString);
        if (count($flagParts) !== 2) {
            return null;
        }
        [$flagID, $flagStringValue] = $flagParts;
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