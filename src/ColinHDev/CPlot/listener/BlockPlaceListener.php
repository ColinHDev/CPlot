<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\block\Block;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

class BlockPlaceListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onBlockPlace(BlockPlaceEvent $event) : void {
        /**
         * @var int $x
         * @var int $y
         * @var int $z
         * @var Block $block
         */
        foreach ($event->getTransaction()->getBlocks() as [$x, $y, $z, $block]) {
            $position = $block->getPosition();
            /** @var true|false|null $isPlotWorld */
            $isPlotWorld = $this->getAPI()->isPlotWorld($position->getWorld())->getResult();
            if ($isPlotWorld !== true) {
                if ($isPlotWorld !== false) {
                    $event->cancel();
                }
                continue;
            }

            /** @var Plot|false|null $plot */
            $plot = $this->getAPI()->getOrLoadPlotAtPosition($position)->getResult();
            if ($plot instanceof Plot) {
                $player = $event->getPlayer();
                if ($player->hasPermission("cplot.place.plot")) {
                    continue;
                }

                if ($plot->isPlotOwner($player)) {
                    continue;
                }
                if ($plot->isPlotTrusted($player)) {
                    continue;
                }
                if ($plot->isPlotHelper($player)) {
                    foreach ($plot->getPlotOwners() as $plotOwner) {
                        $owner = $plotOwner->getPlayerData()->getPlayer();
                        if ($owner !== null) {
                            continue 2;
                        }
                    }
                }

                if ($plot->getFlag(Flags::PLACE())->contains($block)) {
                    continue;
                }

            } else if ($plot === false) {
                if ($event->getPlayer()->hasPermission("cplot.place.road")) {
                    continue;
                }
            }
            $event->cancel();
            break;
        }
    }
}