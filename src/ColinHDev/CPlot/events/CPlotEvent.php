<?php

namespace ColinHDev\CPlot\events;

use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

/**
 * Template-Class for CPlot's events.
 */
abstract class CPlotEvent extends Event
{
    use CancellableTrait;

    /**
     * @var Plot
     */
    private Plot $plot;

    /**
     * @var Player
     */
    private Player $player;


    /**
     * Set/Change the selected plot.
     *
     * @param Plot $plot
     */
    public function setPlot(Plot $plot): void
    {
        $this->plot = $plot;
    }

    /**
     * Returns the selected plot.
     *
     * @return Plot
     */
    public function getPlot(): Plot
    {
        return $this->plot;
    }

    /**
     * Returns the selected player.
     *
     * @return Player
     */
    public function getPlayer(): Player
    {
        return $this->player;
    }

    /**
     * Set/change the selected player.
     *
     * @param Player $player
     */
    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }
}