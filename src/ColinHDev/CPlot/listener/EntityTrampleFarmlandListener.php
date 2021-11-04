<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;

class EntityTrampleFarmlandListener implements Listener {

    public function onEntityTrampleFarmland(EntityTrampleFarmlandEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $entity = $event->getEntity();
        if (!$entity instanceof Player) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) {
            return;
        }

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            if ($entity->hasPermission("cplot.interact.plot")) {
                return;
            }

            try {
                $playerUUID = $entity->getUniqueId()->toString();
                if ($plot->isPlotOwner($playerUUID)) {
                    return;
                }
                if ($plot->isPlotTrusted($playerUUID)) {
                    return;
                }
                if ($plot->isPlotHelper($playerUUID)) {
                    foreach ($plot->getPlotOwners() as $plotOwner) {
                        $owner = $entity->getServer()->getPlayerByUUID(Uuid::fromString($plotOwner->getPlayerUUID()));
                        if ($owner !== null) {
                            return;
                        }
                    }
                }
            } catch (PlotException) {
            }

        } else {
            if ($entity->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}