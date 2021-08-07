<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\Listener;

class BlockSpreadListener implements Listener {

    public function onBlockSpread(BlockSpreadEvent $event) : void {
        if ($event->isCancelled()) return;
        if (!$event->getNewState() instanceof Liquid) return;

        $fromPosition = $event->getBlock()->getPos();
        $world = $fromPosition->getWorld();
        if (CPlot::getInstance()->getProvider()->getWorld($world->getFolderName()) === null) return;

        $plot = Plot::fromPosition($event->getBlock()->getPos());
        if ($plot !== null) {
            $flag = $plot->getFlagByID(FlagIDs::FLAG_FLOWING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}