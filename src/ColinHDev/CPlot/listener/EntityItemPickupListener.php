<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;

class EntityItemPickupListener implements Listener {

    public function onEntityItemPickup(EntityItemPickupEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $entity = $event->getEntity();
        if (!$entity instanceof Player) {
            return;
        }

        $position = $event->getOrigin()->getPosition();
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

            try {
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_ITEM_PICKUP);
                if ($flag->getValue() === true) {
                    return;
                }
            } catch (PlotException) {
            }

        } else {
            return;
        }

        $event->cancel();
    }
}