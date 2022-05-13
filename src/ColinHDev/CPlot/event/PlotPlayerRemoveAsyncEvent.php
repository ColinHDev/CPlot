<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when a {@see PlotPlayer} is removed from a {@see Plot} by a {@see Player}.
 */
class PlotPlayerRemoveAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private PlotPlayer $plotPlayer;
    private Player $player;

    public function __construct(Plot $plot, PlotPlayer $plotPlayer, Player $player) {
        parent::__construct($plot);
        $this->plotPlayer = $plotPlayer;
        $this->player = $player;
    }

    /**
     * Returns the {@see PlotPlayer} that is being removed from the plot.
     */
    public function getPlotPlayer() : PlotPlayer {
        return $this->plotPlayer;
    }

    /**
     * Returns the {@see Player} that removes the {@see PlotPlayer} from the plot.
     */
    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, PlotPlayer $plotPlayer, Player $player) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $plotPlayer, $player) : void {
                $event = new self($plot, $plotPlayer, $player);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}