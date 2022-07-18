<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\GrowingFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\Listener;

class BlockGrowListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockGrow(BlockGrowEvent $event) : void {
        $position = $event->getBlock()->getPosition();
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
        if ($plot instanceof Plot && $plot->getFlag(Flags::GROWING())->equals(GrowingFlag::TRUE())) {
            return;
        }

        $event->cancel();
    }
}