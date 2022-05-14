<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when a {@see Plot} is cleared e.g. by a {@see Player} or another plugin.
 */
class PlotClearAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private ?Player $player;

    public function __construct(Plot $plot, ?Player $player) {
        parent::__construct($plot);
        $this->player = $player;
    }

    /**
     * This method returns the {@see Player} who cleared the {@see Plot} or null if it was cleared e.g. by another plugin.
     */
    public function getPlayer() : ?Player {
        return $this->player;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, ?Player $player) : \Generator {
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