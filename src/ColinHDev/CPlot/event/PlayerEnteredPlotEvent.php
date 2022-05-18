<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\event;

use ColinHDev\CPlot\plots\Plot;
use pocketmine\player\Player;

/**
 * This event is ALWAYS called when a {@see Player} entered a {@see Plot}. But this does not mean, that the player entered
 * the plot at that moment. He could already have entered the plot when this event is called.
 */
class PlayerEnteredPlotEvent extends PlotEvent {

    private Player $player;

    public function __construct(Plot $plot, Player $player) {
        parent::__construct($plot);
        $this->player = $player;
    }

    public function getPlayer() : Player {
        return $this->player;
    }
}