<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockGrowListener implements Listener {

    public function onBlockGrow(BlockGrowEvent $event) : void {
        if ($event->isCancelled()) return;

        $world = $event->getBlock()->getPos()->getWorld();
        $position = $event->getNewState()->getPos()->asVector3();
        if (CPlot::getInstance()->getProvider()->getWorld($world->getFolderName()) === null) return;

        $plot = Plot::fromPosition(Position::fromObject($position, $world));
        if ($plot !== null) {
            $flag = $plot->getFlagByID(FlagIDs::FLAG_GROWING);
            if ($flag !== null && $flag->getValueNonNull() === true) return;
        }

        $event->cancel();
    }
}