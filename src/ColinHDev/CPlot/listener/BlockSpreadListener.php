<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\Listener;

class BlockSpreadListener implements Listener {

    public function onBlockSpread(BlockSpreadEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }
        if (!$event->getNewState() instanceof Liquid) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) {
            return;
        }

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            try {
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_FLOWING);
                if ($flag->getValue() === true) {
                    return;
                }
            } catch (PlotException) {
            }
        }

        $event->cancel();
    }
}