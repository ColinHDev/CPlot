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

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) return;

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_BURNING);
            if ($flag !== null && $flag->getValue() === true) return;
        }

        $event->cancel();
    }
}