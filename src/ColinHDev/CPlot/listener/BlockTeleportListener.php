<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\block\BlockTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockTeleportListener implements Listener {

    public function onBlockTeleport(BlockTeleportEvent $event) : void {
        if ($event->isCancelled()) return;

        $fromPosition = $event->getBlock()->getPosition();
        $world = $fromPosition->getWorld();
        if (CPlot::getInstance()->getProvider()->getWorld($world->getFolderName()) === null) return;

        $toPosition = Position::fromObject($event->getTo(), $world);

        $fromBasePlot = BasePlot::fromPosition($fromPosition);
        $toBasePlot = BasePlot::fromPosition($toPosition);
        if ($fromBasePlot !== null && $toBasePlot !== null && $fromBasePlot->isSame($toBasePlot)) return;

        $fromPlot = $fromBasePlot?->toPlot() ?? Plot::fromPosition($fromPosition);
        $toPlot = $toBasePlot?->toPlot() ?? Plot::fromPosition($toPosition);
        if ($fromPlot !== null && $toPlot !== null && $fromPlot->isSame($toPlot)) return;

        $event->cancel();
    }
}