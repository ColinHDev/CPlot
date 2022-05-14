<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use SOFe\AwaitGenerator\Await;

/**
 * This event is called when the biome of a {@see Plot} is changed.
 */
class PlotBiomeChangeAsyncEvent extends PlotAsyncEvent implements Cancellable {
    use CancellableTrait;

    /** @phpstan-var BiomeIds::* */
    private int $biomeID;

    /**
     * @phpstan-param BiomeIds::* $biomeID
     */
    public function __construct(Plot $plot, int $biomeID) {
        parent::__construct($plot);
        $this->biomeID = $biomeID;
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

    /**
     * @phpstan-param BiomeIds::* $biomeID
     * @phpstan-return \Generator<mixed, Await::RESOLVE|null|Await::RESOLVE_MULTI|Await::REJECT|Await::ONCE|Await::ALL|Await::RACE|\Generator, mixed, self>
     */
    public static function create(Plot $plot, int $biomeID) : \Generator {
        $event = yield from Await::promise(
            static function ($onSuccess, $onError) use ($plot, $biomeID) : void {
                $event = new self($plot, $biomeID);
                $event->setCallback($onSuccess);
                $event->call();
            }
        );
        assert($event instanceof self);
        return $event;
    }
}