<?php

namespace ColinHDev\CPlot\events;

use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\block\Block;
use pocketmine\player\Player;

class CPlotBlockEvent extends CPlotEvent
{

    /**
     * Unknown action type.
     */
    public const ACTION_UNKNOWN = 0;

    /**
     * Action: on break a block on a plot.
     */
    public const ACTION_BREAK = 1;

    /**
     * Action: on place a block on a plot.
     */
    public const ACTION_PLACE = 2;

    /**
     * Action: on interact with a block on a plot.
     */
    public const ACTION_INTERACT = 3;

    /**
     * @param Plot $plot
     * @param Player $player
     * @param Block $block
     * @param int $action
     */
    public function __construct(private Plot $plot, private  Player $player, private Block $block, private int $action = 0)
    {
        if ($action > 3 || $action < 0) throw new \InvalidArgumentException("CPlotBlockEvent: Action cannot be over 3 or under 0, " . (string)$this->action . " given!");
    }

    /**
     * @return Block
     */
    public function getBlock(): Block
    {
        return $this->block;
    }

    /**
     * @param Block $block
     */
    public function setBlock(Block $block): void
    {
        $this->block = $block;
    }

    /**
     * @return int
     */
    public function getAction(): int
    {
        return $this->action;
    }
}