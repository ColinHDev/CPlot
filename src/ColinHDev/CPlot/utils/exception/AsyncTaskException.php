<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils\exception;

use Exception;
use Throwable;

class AsyncTaskException extends Exception {

    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
    }
}