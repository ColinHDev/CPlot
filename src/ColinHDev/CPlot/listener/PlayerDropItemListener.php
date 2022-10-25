<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\ItemDropFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;

class PlayerDropItemListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event) : void {
        $player = $event->getPlayer();
        $position = $player->getPosition();
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

            if ($plot->getFlag(Flags::ITEM_DROP())->equals(ItemDropFlag::TRUE())) {
                return;
            }
        }

        $event->cancel();
    }
}