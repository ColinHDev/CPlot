<?php

namespace ColinHDev\CPlot\events;

use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\event\Event;
use pocketmine\player\Player;

class CPlotEnterEvent extends CPlotEvent
{

    public function __construct(private Plot $plot, private Player $player) {}
}