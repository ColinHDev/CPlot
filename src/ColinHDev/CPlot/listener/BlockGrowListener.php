<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockGrowListener implements Listener {

    public function onBlockGrow(BlockGrowEvent $event) : void {
        if ($event->isCancelled()) return;

        $plot = Plot::fromPosition(
            Position::fromObject(
                $event->getNewState()->getPosition()->asVector3(),
                $event->getBlock()->getPosition()->getWorld()
            )
        );
        if ($plot !== null) {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}