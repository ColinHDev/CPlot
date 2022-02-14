<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
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
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            LanguageManager::getInstance()->getProvider()->sendMessage($entity, ["prefix", "player.interact.worldNotLoaded"]);
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            LanguageManager::getInstance()->getProvider()->sendMessage($entity, ["prefix", "player.interact.plotNotLoaded"]);
            $event->cancel();
            return;
        }
        if ($plot instanceof Plot) {
            if ($entity->hasPermission("cplot.interact.plot")) {
                return;
            }

            $playerUUID = $entity->getUniqueId()->getBytes();
            if ($plot->isPlotOwner($playerUUID)) {
                return;
            }
            if ($plot->isPlotTrusted($playerUUID)) {
                return;
            }
            if ($plot->isPlotHelper($playerUUID)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $entity->getServer()->getPlayerByUUID(Uuid::fromBytes($plotOwner->getPlayerUUID()));
                    if ($owner !== null) {
                        return;
                    }
                }
            }

        } else {
            if ($entity->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}