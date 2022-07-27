<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\flags\FlagIDs;
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
                /** @var BooleanAttribute $flag */
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVP);
                if ($flag->getValue() === true) {
                    return;
                }

            } else if ($plot === false) {
                if ($damager->hasPermission("cplot.pvp.road")) {
                    return;
                }
            }

        // pve flag
        } else {
            if ($plot instanceof Plot) {
                /** @var BooleanAttribute $flag */
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVE);
                if ($flag->getValue() === true) {
                    return;
                }
            }
        }

        $event->cancel();
    }
}