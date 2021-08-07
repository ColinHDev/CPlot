<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\Listener;

class BlockBurningListener implements Listener {

    public function onBlockBurn(BlockBurnEvent $event) : void {
        if ($event->isCancelled()) return;

        $position = $event->getBlock()->getPos();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) return;

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            $flag = $plot->getFlagByID(FlagIDs::FLAG_BURNING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}