<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class PlotClearEvent extends CPlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private Plot $plot;
    private Player $player;

    public function __construct(Plot $plot, Player $player) {
        $this->plot = $plot;
        $this->player = $player;
    }

    public function getPlot() : Plot {
        return $this->plot;
    }

    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * @phpstan-return \Generator<mixed, AwaitGeneratorPromiseMethod, self, self>
     */
    public static function create(Plot $plot, Player $player) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $player) : void {
                $event = new self($plot, $player);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}