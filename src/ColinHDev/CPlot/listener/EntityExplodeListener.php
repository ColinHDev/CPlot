<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\ExplosionFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;

class EntityExplodeListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onEntityExplode(EntityExplodeEvent $event) : void {
        if (count($event->getBlockList()) === 0) {
            return;
        }

        $position = $event->getPosition();
        /** @phpstan-var true|false|null $isPlotWorld */
        $isPlotWorld = $this->getAPI()->isPlotWorld($position->getWorld())->getResult();
        if ($isPlotWorld !== true) {
            if ($isPlotWorld !== false) {
                $event->cancel();
            }
            return;
        }

        /** @phpstan-var Plot|false|null $plot */
        $plot = $this->getAPI()->getOrLoadPlotAtPosition($position)->getResult();
        if ($plot instanceof Plot && $plot->getFlag(Flags::EXPLOSION())->equals(ExplosionFlag::TRUE())) {
            $affectedBlocks = [];
            foreach ($event->getBlockList() as $hash => $block) {
                if ($plot->isOnPlot($block->getPosition())) {
                    $affectedBlocks[$hash] = $block;
                }
            }
            $event->setBlockList($affectedBlocks);
            return;
        }

        $event->cancel();
    }
}