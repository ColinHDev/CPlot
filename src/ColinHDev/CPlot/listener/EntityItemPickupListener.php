<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\BooleanAttribute;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
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

        $position = $entity->getPosition();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $entity->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.interact.worldNotLoaded"));
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            $entity->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.interact.plotNotLoaded"));
            $event->cancel();
            return;
        }
        if ($plot !== null) {
            if ($entity->hasPermission("cplot.interact.plot")) {
                return;
            }

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

            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_ITEM_PICKUP);
            if ($flag->getValue() === true) {
                return;
            }

        } else {
            return;
        }

        $event->cancel();
    }
}