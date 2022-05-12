<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class PlotBorderChangeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    private Block $block;
    private Player $player;

    public function __construct(Plot $plot, Block $block, Player $player) {
        parent::__construct($plot);
        $this->block = $block;
        $this->player = $player;
    }

    public function getBlock() : Block {
        return $this->block;
    }

    public function setBlock(Block $block) : void {
        $this->block = $block;
    }

    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, Block $block, Player $player) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $block, $player) : void {
                $event = new self($plot, $block, $player);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}