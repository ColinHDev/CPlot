<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;

class PlayerBucketEmptyListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event) : void {
        $position = $event->getBlockClicked()->getPosition();
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
            if ($player->hasPermission("cplot.interact.plot")) {
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

        } else if ($plot === false && $event->getPlayer()->hasPermission("cplot.interact.road")) {
            return;
        }

        $event->cancel();
    }
}