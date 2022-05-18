<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

/**
 * This event is SOMETIMES called when a {@see Player} leaves a {@see Plot} at exactly that moment. Because of this, the
 * event can still be cancelled.
 * Because of some internal limitations, there is no guarantee that this event is actually called when a player leaves a
 * plot. Therefore, you should rely on {@see PlayerLeftPlotEvent} in most cases instead.
 */
class PlayerLeavePlotEvent extends PlayerLeftPlotEvent implements Cancellable {
    use CancellableTrait;
}