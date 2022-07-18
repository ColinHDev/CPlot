<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\PveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Egg;
use pocketmine\entity\projectile\Snowball;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class ProjectileLaunchListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onProjectileLaunch(ProjectileLaunchEvent $event) : void {
        $entity = $event->getEntity();
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
        if (!($plot instanceof Plot)) {
            $event->cancel();
            return;
        }

        $owningEntity = $entity->getOwningEntity();
        if (!($owningEntity instanceof Player)) {
            if ($owningEntity !== null && $plot->getFlag(Flags::PVE())->equals(PveFlag::TRUE())) {
                return;
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

        if (
            (
                $entity instanceof Arrow ||
                $entity instanceof Egg ||
                $entity instanceof Snowball ||
                $entity instanceof SplashPotion
            ) &&
            $plot->getFlag(Flags::PVP())->equals(PvpFlag::TRUE())
        ) {
            return;
        }

        $event->cancel();
    }
}