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

    // TODO: change codes to something more universally usable
    public const SOURCE_UNDERFLOW = 1;
    public const DESTINATION_OVERFLOW = 2;
    public const NO_SUCH_ACCOUNT = 3;
    public const NO_SUCH_TRANSACTION = 4;
    public const ACCOUNT_LABEL_ALREADY_EXISTS = 5;
    public const ACCOUNT_LABEL_DOES_NOT_EXIST = 6;
    public const TRANSACTION_LABEL_ALREADY_EXISTS = 7;
    public const TRANSACTION_LABEL_DOES_NOT_EXIST = 8;
    public const EVENT_CANCELLED = 9;

    public function __construct(int $code, ?Exception $previous = null) {
        parent::__construct("", $code, $previous);
    }

    public function getLanguageKey() : string {
        return match ($this->code) {
            self::SOURCE_UNDERFLOW => "Source account resultant value is too low",
            self::DESTINATION_OVERFLOW => "Destination account resultant value is too high",
            self::NO_SUCH_ACCOUNT => "The account does not exist",
            self::NO_SUCH_TRANSACTION => "The transaction does not exist",
            self::ACCOUNT_LABEL_ALREADY_EXISTS => "The account already has this label",
            self::ACCOUNT_LABEL_DOES_NOT_EXIST => "The account does not have this label",
            self::TRANSACTION_LABEL_ALREADY_EXISTS => "The transaction already has this label",
            self::TRANSACTION_LABEL_DOES_NOT_EXIST => "The transaction does not have this label",
            self::EVENT_CANCELLED => "The transaction event was cancelled"
        };
    }
}