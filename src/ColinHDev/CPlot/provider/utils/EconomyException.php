<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\provider\utils;

use Exception;

/**
 * This exception is thrown in our {@see EconomyProvider} child classes when something during a transaction goes wrong.
 * By using this universal approach, we can easily handle the behaviour of different economy providers at once, since
 * some may throw an exception themselves if anything goes wrong while others might simply return false.
 */
class EconomyException extends Exception {

    // This error code is used if the reason why a transaction failed is unknown.
    public const UNKNOWN = 1;
    // This error code is used if the transaction's event within the economy plugin was cancelled.
    public const EVENT_CANCELLED = 2;
    // This error code is used if the player does not have an account within the economy plugin.
    public const SOURCE_NON_EXISTENT = 3;
    // This error code is used if the player does not have enough money to complete the transaction.
    public const SOURCE_UNDERFLOW = 4;

    /**
     * @phpstan-param self::* $code
     */
    public function __construct(int $code = self::UNKNOWN, ?Exception $previous = null) {
        parent::__construct("", $code, $previous);
    }

    public function getLanguageKey() : string {
        return match ($this->code) {
            self::UNKNOWN => "economy.error.unknown",
            self::EVENT_CANCELLED => "economy.error.eventCancelled",
            self::SOURCE_NON_EXISTENT => "economy.error.sourceNonExistent",
            self::SOURCE_UNDERFLOW => "economy.error.sourceUnderflow"
        };
    }
}