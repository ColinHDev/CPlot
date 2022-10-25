<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\PveFlag;
use ColinHDev\CPlot\plots\flags\implementation\PvpFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityDamageByEntityListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void {
        $damager = $event->getDamager();
        if (!$damager instanceof Player) {
            return;
        }
        $damaged = $event->getEntity();

        /** @phpstan-var true|false|null $isPlotWorld */
        $isPlotWorld = $this->getAPI()->isPlotWorld($damaged->getWorld())->getResult();
        if ($isPlotWorld !== true) {
            if ($isPlotWorld !== false) {
                $event->cancel();
            }
            return;
        }

        /** @phpstan-var Plot|false|null $plot */
        $plot = $this->getAPI()->getOrLoadPlotAtPosition($damaged->getPosition())->getResult();

        // pvp flag
        if ($damaged instanceof Player) {
            if ($plot instanceof Plot) {
                if ($damager->hasPermission("cplot.pvp.plot")) {
                    return;
                }
                if ($plot->getFlag(Flags::PVP())->equals(PvpFlag::TRUE())) {
                    return;
                }

            } else if ($plot === false) {
                if ($damager->hasPermission("cplot.pvp.road")) {
                    return;
                }
            }

        // pve flag
        } else if ($plot instanceof Plot && $plot->getFlag(Flags::PVE())->equals(PveFlag::TRUE())) {
            return;
        }

        $event->cancel();
    }
}