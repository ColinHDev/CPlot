<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\FlowingFlag;
use ColinHDev\CPlot\plots\flags\implementation\GrowingFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\Listener;

class BlockSpreadListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockSpread(BlockSpreadEvent $event) : void {
        $position = $event->getSource()->getPosition();
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
        // We not only need to check if the source is on the plot but also if that applies for the changed block.
        if ($plot instanceof Plot && $plot->isOnPlot($event->getBlock()->getPosition())) {
            if ($event->getNewState() instanceof Liquid) {
                $flag = $plot->getFlag(Flags::FLOWING());
                $flagToCompare = FlowingFlag::TRUE();
            } else {
                $flag = $plot->getFlag(Flags::GROWING());
                $flagToCompare = GrowingFlag::TRUE();
            }
            if ($flag->equals($flagToCompare)) {
                return;
            }
        }

        $event->cancel();
    }
}