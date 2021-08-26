<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;

class StructureGrowListener implements Listener {

    public function onStructureGrow(StructureGrowEvent $event) : void {
        if ($event->isCancelled()) return;

        $block = $event->getBlock();
        $position = $block->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) return;

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}