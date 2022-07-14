<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\Listener;

class BlockBurnListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockBurn(BlockBurnEvent $event) : void {
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
        if ($plot instanceof Plot) {
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagByID(FlagIDs::FLAG_BURNING);
            if ($flag->getValue() === true) {
                return;
            }
        }

        $event->cancel();
    }
}