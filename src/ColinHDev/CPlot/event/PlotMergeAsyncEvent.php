<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class PlotMergeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private Plot $plotToMerge;
    private Player $player;

    public function __construct(Plot $plot, Plot $plotToMerge, Player $player) {
        parent::__construct($plot);
        $this->plotToMerge = $plotToMerge;
        $this->player = $player;
    }

    public function getPlotToMerge() : Plot {
        return $this->plotToMerge;
    }

    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, Plot $plotToMerge, Player $player) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $plotToMerge, $player) : void {
                $event = new self($plot, $plotToMerge, $player);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}