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
            [$flagID, $flagStringValue] = $flagParts;
            $flag = FlagManager::getInstance()->getFlagByID($flagID);
            if ($flag instanceof Flag && !($flag instanceof InternalFlag)) {
                try {
                    $flagValue = $flag->parse($flagStringValue);
                } catch(AttributeParseException) {
                    throw new AttributeParseException($this, $value);
                }
                return [$flag->createInstance($flagValue)];
            }
        } else if (count($flagParts) > 2) {
            try {
                $flags = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($flags)) {
                    $parsedFlags = [];
                    foreach ($flags as $flagParts) {
                        if (!is_string($flagParts)) {
                            throw new AttributeParseException($this, $value);
                        }
                        $flagParts = explode("=", $flagParts);
                        if (count($flagParts) === 2) {
                            [$flagID, $flagStringValue] = $flagParts;
                            $flag = FlagManager::getInstance()->getFlagByID($flagID);
                            if ($flag instanceof Flag && !($flag instanceof InternalFlag)) {
                                try {
                                    $flagValue = $flag->parse($flagStringValue);
                                } catch(AttributeParseException) {
                                    throw new AttributeParseException($this, $value);
                                }
                                $parsedFlags[] = $flag->createInstance($flagValue);
                            }
                        }
                    }
                    return $parsedFlags;
                }
            } catch(JsonException) {
            }
        }
        throw new AttributeParseException($this, $value);
    }
}