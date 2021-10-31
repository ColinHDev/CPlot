<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;

class EntityExplodeListener implements Listener {

    public function onEntityExplode(EntityExplodeEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }
        if (count($event->getBlockList()) === 0) {
            return;
        }

        $position = $event->getPosition();
        $world = $position->getWorld();
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($world->getFolderName());
        if ($worldSettings === null) {
            return;
        }

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            try {
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_EXPLOSION);
                if ($flag->getValue() === true) {
                    $affectedBlocks = [];
                    foreach ($event->getBlockList() as $hash => $block) {
                        if ($plot->isOnPlot($block->getPosition())) {
                            $affectedBlocks[$hash] = $block;
                        }
                    }
                    $event->setBlockList($affectedBlocks);
                    return;
                }
            } catch (PlotException) {
            }
        }

        $event->cancel();
    }
}