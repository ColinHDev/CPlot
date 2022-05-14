<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when the border of a {@see Plot} is changed.
 */
class PlotBorderChangeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private Block $block;

    public function __construct(Plot $plot, Block $block) {
        parent::__construct($plot);
        $this->block = $block;
    }

    public function getBlock() : Block {
        return $this->block;
    }

    public function setBlock(Block $block) : void {
        $this->block = $block;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, Block $block) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $block) : void {
                $event = new self($plot, $block);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}