<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\PveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityShootBowListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onEntityShootBow(EntityShootBowEvent $event) : void {
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

        if (!($entity instanceof Player)) {
            if ($plot->getFlag(Flags::PVE())->equals(PveFlag::TRUE())) {
                return;
            }
            $event->cancel();
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

        if ($plot->getFlag(Flags::PVP())->equals(PvpFlag::TRUE())) {
            return;
        }

        $event->cancel();
    }
}