<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class StructureGrowListener implements Listener {

    public function onStructureGrow(StructureGrowEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        $world = $position->getWorld();
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($world->getFolderName());
        if ($worldSettings === null) {
            return;
        }

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            try {
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
                if ($flag->getValue() === true) {
                    $transaction = $event->getTransaction();
                    foreach ($transaction->getBlocks() as [$x, $y, $z, $block]) {
                        if ($plot->isOnPlot(new Position($x, $y, $z, $world))) {
                            continue;
                        }
                        $transaction->addBlockAt($x, $y, $z, $world->getBlockAt($x, $y, $z));
                    }
                    return;
                }
            } catch (PlotException) {
            }
        }

        $event->cancel();
    }
}