<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\plots\utils;

use Exception;
use Throwable;

class PlotModificationException extends Exception {

    public const EVENT_CANCELLED = 1;
    public const CHUNK_LOCKED = 2;
    public const ASYNC_TASK_FAILED = 3;
    public const DATABASE_ERROR = 4;
    public const WORLD_NOT_LOADABLE = 5;
    public const CHUNK_LOCK_CHANGED = 6;

    /**
     * @phpstan-param self::* $code
     */
    public function __construct(int $code, string $message = "", ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}