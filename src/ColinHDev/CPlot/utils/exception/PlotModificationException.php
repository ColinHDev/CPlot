<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils\exception;

use ColinHDev\CPlot\plots\Plot;
use Exception;
use Throwable;

/**
 * Wrapper exception for all exceptions thrown during the modification of a {@see Plot}. This includes but is not
 * limited to the following methods:
 * @see Plot::setBiome()
 * @see Plot::setBorderBlock()
 * @see Plot::setWallBlock()
 * @see Plot::merge()
 * @see Plot::clear()
 * @see Plot::reset()
 */
class PlotModificationException extends Exception {

    public function __construct(string $message, Throwable $previous) {
        parent::__construct($message, 0, $previous);
    }
}