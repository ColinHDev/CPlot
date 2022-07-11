<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class ProjectileLaunchListener implements Listener {

    /**
     * @handleCancelled false
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event) : void {
        $entity = $event->getEntity();
        $position = $entity->getPosition();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            if ($worldSettings !== false) {
                $event->cancel();
                return;
            }
            return;
        }
        $plot = Plot::loadFromPositionIntoCache($position);
        if (!($plot instanceof Plot)) {
            $event->cancel();
            return;
        }

        $owningEntity = $entity->getOwningEntity();
        if (!($owningEntity instanceof Player)) {
            if ($owningEntity !== null) {
                /** @var BooleanAttribute $flag */
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVE);
                if ($flag->getValue() === true) {
                    return;
                }
            }
            $event->cancel();
            return;
        }

        if ($plot->isPlotOwner($owningEntity)) {
            return;
        }
        if ($plot->isPlotTrusted($owningEntity)) {
            return;
        }
        if ($plot->isPlotHelper($owningEntity)) {
            foreach ($plot->getPlotOwners() as $plotOwner) {
                $owner = $plotOwner->getPlayerData()->getPlayer();
                if ($owner !== null) {
                    return;
                }
            }
        }

        if ($entity instanceof Arrow || $entity instanceof Egg || $entity instanceof Snowball || $entity instanceof SplashPotion) {
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVP);
            if ($flag->getValue() === true) {
                return;
            }
        }

        $event->cancel();
    }
}