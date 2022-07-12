<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\event\block\BlockTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class BlockTeleportListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockTeleport(BlockTeleportEvent $event) : void {
        $fromPosition = $event->getBlock()->getPosition();
        $world = $fromPosition->getWorld();
        /** @phpstan-var WorldSettings|false|null $isPlotWorld */
        $worldSettings = $this->getAPI()->getOrLoadWorldSettings($world)->getResult();
        if (!($worldSettings instanceof WorldSettings)) {
            if ($worldSettings !== false) {
                $event->cancel();
            }
            return;
        }

        $fromBasePlot = $this->getAPI()->getBasePlotAtPoint($world->getFolderName(), $worldSettings, $fromPosition);
        if ($fromBasePlot instanceof BasePlot) {
            $toPosition = Position::fromObject($event->getTo(), $world);
            if ($fromBasePlot->isOnPlot($toPosition)) {
                return;
            }
            /** @phpstan-var Plot|false|null $fromPlot */
            $fromPlot = $this->getAPI()->getOrLoadPlot($world, $fromBasePlot->getX(), $fromBasePlot->getZ())->getResult();
            if ($fromPlot instanceof Plot && $fromPlot->isOnPlot($toPosition)) {
                return;
            }
        }

        $event->cancel();
    }
}