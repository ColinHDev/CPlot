<?php

namespace ColinHDev\CPlot\events;

use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\player\Player;

class CPlotLeaveEvent extends CPlotEvent
{

    public function __construct(private Plot $plot, private Player $player) {}
}