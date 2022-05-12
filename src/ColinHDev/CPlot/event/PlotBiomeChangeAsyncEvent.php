<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class PlotBiomeChangeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    /** @phpstan-var BiomeIds::* */
    private int $biomeID;
    private Player $player;

    /**
     * @phpstan-param BiomeIds::* $biomeID
     */
    public function __construct(Plot $plot, int $biomeID, Player $player) {
        parent::__construct($plot);
        $this->biomeID = $biomeID;
        $this->player = $player;
    }

    /**
     * @phpstan-return BiomeIds::*
     */
    public function getBiomeID() : int {
        return $this->biomeID;
    }

    /**
     * @phpstan-param BiomeIds::* $biomeID
     */
    public function setBiomeID(int $biomeID) : void {
        $this->biomeID = $biomeID;
    }

    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * @phpstan-param BiomeIds::* $biomeID
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, int $biomeID, Player $player) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $biomeID, $player) : void {
                $event = new self($plot, $biomeID, $player);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}