<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;

class BlockBreakListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockBreak(BlockBreakEvent $event) : void {
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
            $player = $event->getPlayer();
            if ($player->hasPermission("cplot.break.plot")) {
                return;
            }

            if ($plot->isPlotOwner($player)) {
                return;
            }
            if ($plot->isPlotTrusted($player)) {
                return;
            }
            if ($plot->isPlotHelper($player)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner !== null) {
                        return;
                    }
                }
            }

            $block = $event->getBlock();
            /** @var Block $value */
            foreach ($plot->getFlag(Flags::BREAK())->getValue() as $value) {
                if ($block->isSameType($value)) {
                    return;
                }
            }

        } else if ($plot === false) {
            if ($event->getPlayer()->hasPermission("cplot.break.road")) {
                return;
            }
        }

        $event->cancel();
    }
}