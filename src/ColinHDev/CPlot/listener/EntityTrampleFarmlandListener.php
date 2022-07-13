<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityTrampleFarmlandListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onEntityTrampleFarmland(EntityTrampleFarmlandEvent $event) : void {
        $entity = $event->getEntity();
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
            if (!($entity instanceof Player)) {
                $owningEntity = $entity->getOwningEntity();
                if ($owningEntity instanceof Player) {
                    $entity = $owningEntity;
                } else {
                    return;
                }
            }
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

        } else if ($plot === false) {
            if ($entity instanceof Player && $entity->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}