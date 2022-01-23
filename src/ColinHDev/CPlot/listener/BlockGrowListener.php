<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\attributes\BooleanAttribute;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;

class BlockGrowListener implements Listener {

    public function onBlockGrow(BlockGrowEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            $event->cancel();
            return;
        }
        if ($plot !== null) {
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_GROWING);
            if ($flag->getValue() === true) {
                return;
            }
        }

        $event->cancel();
    }
}