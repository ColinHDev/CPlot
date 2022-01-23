<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\event\block\BlockTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockTeleportListener implements Listener {

    public function onBlockTeleport(BlockTeleportEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $fromPosition = $event->getBlock()->getPosition();
        $world = $fromPosition->getWorld();
        $worldName = $world->getFolderName();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($worldName);
        if (!$worldSettings instanceof WorldSettings) {
            if ($worldSettings === null) {
                $event->cancel();
            }
            return;
        }

        $toVector3 = $event->getTo();
        $fromBasePlot = BasePlot::fromVector3($worldName, $worldSettings, $fromPosition);
        $toBasePlot = BasePlot::fromVector3($worldName, $worldSettings, $toVector3);
        if ($fromBasePlot !== null && $toBasePlot !== null && $fromBasePlot->isSame($toBasePlot)) {
            return;
        }

        $fromPlot = $fromBasePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache($fromPosition);
        $toPlot = $toBasePlot?->toSyncPlot() ?? Plot::loadFromPositionIntoCache(Position::fromObject($toVector3, $world));
        if ($fromPlot instanceof Plot && $toPlot instanceof Plot && $fromPlot->isSame($toPlot)) {
            return;
        }

        $event->cancel();
    }
}