<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;

class BlockUpdateListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockUpdate(BlockUpdateEvent $event) : void {
        $position = $event->getBlock()->getPosition();
        $world = $position->getWorld();
        /** @var WorldSettings|false|null $worldSettings */
        $worldSettings = $this->getAPI()->getOrLoadWorldSettings($world)->getResult();
        if (!($worldSettings instanceof WorldSettings)) {
            if ($worldSettings !== false) {
                $event->cancel();
            }
            return;
        }

        /** @var BasePlot|false $basePlot */
        $basePlot = $this->getAPI()->getBasePlotAtPoint($world->getFolderName(), $worldSettings, $position);
        if ($basePlot instanceof BasePlot) {
            return;
        }

        /** @var Plot|false|null $basePlot */
        $plot = $this->getAPI()->getOrLoadPlotAtPosition($position)->getResult();
        if ($plot instanceof Plot) {
            return;
        }

        $event->cancel();
    }
}