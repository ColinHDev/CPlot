<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\GrowingFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\block\StructureGrowEvent;
use pocketmine\event\Listener;
use pocketmine\world\Position;

class StructureGrowListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onStructureGrow(StructureGrowEvent $event) : void {
        $position = $event->getBlock()->getPosition();
        $world = $position->getWorld();
        /** @phpstan-var true|false|null $isPlotWorld */
        $isPlotWorld = $this->getAPI()->isPlotWorld($world)->getResult();
        if ($isPlotWorld !== true) {
            if ($isPlotWorld !== false) {
                $event->cancel();
            }
            return;
        }

        /** @phpstan-var Plot|false|null $plot */
        $plot = $this->getAPI()->getOrLoadPlotAtPosition($position)->getResult();
        if ($plot instanceof Plot && $plot->getFlag(Flags::GROWING())->equals(GrowingFlag::TRUE())) {
            $transaction = $event->getTransaction();
            foreach ($transaction->getBlocks() as [$x, $y, $z, $block]) {
                if (!$plot->isOnPlot(new Position($x, $y, $z, $world))) {
                    $transaction->addBlockAt($x, $y, $z, $world->getBlockAt($x, $y, $z));
                }
            }
            return;
        }

        $event->cancel();
    }
}