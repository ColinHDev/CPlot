<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\ItemPickupFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityItemPickupListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onEntityItemPickup(EntityItemPickupEvent $event) : void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) {
            return;
        }

        $position = $entity->getPosition();
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
            if ($entity->hasPermission("cplot.interact.plot")) {
                return;
            }

            if ($plot->isPlotOwner($entity)) {
                return;
            }
            if ($plot->isPlotTrusted($entity)) {
                return;
            }
            if ($plot->isPlotHelper($entity)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner !== null) {
                        return;
                    }
                }
            }

            if ($plot->getFlag(Flags::ITEM_PICKUP())->equals(ItemPickupFlag::TRUE())) {
                return;
            }

        } else if ($plot === false) {
            return;
        }

        $event->cancel();
    }
}