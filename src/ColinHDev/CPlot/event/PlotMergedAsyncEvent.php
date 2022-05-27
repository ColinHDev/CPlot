<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when two {@see Plot}s were successfully merged into one single {@see Plot}.
 */
class PlotMergedAsyncEvent extends PlotAsyncEvent {

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