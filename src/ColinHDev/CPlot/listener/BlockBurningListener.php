<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\Listener;

class BlockBurningListener implements Listener {

    public function onBlockBurn(BlockBurnEvent $event) : void {
        if ($event->isCancelled()) return;

        $plot = Plot::fromPosition($event->getBlock()->getPosition());
        if ($plot !== null) {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_BURNING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}