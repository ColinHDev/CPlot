<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when two {@see Plot}s are merged.
 */
class PlotMergeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private Plot $plotToMerge;

    public function __construct(Plot $plot, Plot $plotToMerge) {
        parent::__construct($plot);
        $this->plotToMerge = $plotToMerge;
    }

    public function getPlotToMerge() : Plot {
        return $this->plotToMerge;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, Plot $plotToMerge) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $plotToMerge) : void {
                $event = new self($plot, $plotToMerge);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}