<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use Generator;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use function assert;

/**
 * This event is called when the {@see getTarget()} is kicked from the {@see getPlot()} by the {@see getPlayer()}.
 */
class PlayerKickFromPlotEvent extends PlotEvent implements Cancellable {
    use CancellableTrait;

    private Player $player;
    private Player $target;

    public function __construct(Plot $plot, Player $player, Player $target) {
        parent::__construct($plot);
        $this->player = $player;
        $this->target = $target;
    }

    /**
     * Returns the player who is kicking the @see getTarget().
     */
    public function getPlayer() : Player {
        return $this->player;
    }

    /**
     * Returns the player who gets kicked by the @see getPlayer().
     */
    public function getTarget() : Player {
        return $this->target;
    }
}