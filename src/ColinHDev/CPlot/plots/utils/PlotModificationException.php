<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\utils;

use Exception;
use Throwable;

class PlotModificationException extends Exception {

    public const PLOT_LOCKED = 1;
    public const EVENT_CANCELLED = 2;
    public const CHUNK_LOCKED = 3;
    public const ASYNC_TASK_FAILED = 4;
    public const DATABASE_ERROR = 5;
    public const WORLD_NOT_LOADABLE = 6;
    public const CHUNK_LOCK_CHANGED = 7;

    /**
     * @phpstan-param self::* $code
     */
    public function __construct(int $code, string $message = "", ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getLanguageKey() : string {
        return match ($this->code) {
            self::PLOT_LOCKED => "plotModification.error.plotLocked",
            self::EVENT_CANCELLED => "plotModification.error.eventCancelled",
            self::CHUNK_LOCKED => "plotModification.error.chunkLocked",
            self::ASYNC_TASK_FAILED => "plotModification.error.asyncTaskFailed",
            self::DATABASE_ERROR => "plotModification.error.databaseError",
            self::WORLD_NOT_LOADABLE => "plotModification.error.worldNotLoadable",
            self::CHUNK_LOCK_CHANGED => "plotModification.error.chunkLockChanged",
            default => $this->message
        };
    }
}