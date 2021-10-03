<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockGrowListener implements Listener {

    public function onBlockGrow(BlockGrowEvent $event) : void {
        if ($event->isCancelled()) return;

        $world = $event->getBlock()->getPosition()->getWorld();
        if (CPlot::getInstance()->getProvider()->getWorld($world->getFolderName()) === null) return;

        $position = $event->getNewState()->getPosition()->asVector3();
        $plot = Plot::fromPosition(Position::fromObject($position, $world));
        if ($plot !== null) {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
            if ($flag !== null && $flag->getValue() === true) return;
        }

        $event->cancel();
    }
}