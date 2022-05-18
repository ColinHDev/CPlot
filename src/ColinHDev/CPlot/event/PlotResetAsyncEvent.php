<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when a {@see Plot} is reset.
 */
class PlotResetAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot) : void {
                $event = new self($plot);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}