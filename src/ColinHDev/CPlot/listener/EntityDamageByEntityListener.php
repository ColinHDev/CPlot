<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityDamageByEntityListener implements Listener {

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void {
        if ($event->isCancelled()) return;

        $damager = $event->getDamager();
        if (!$damager instanceof Player) return;

        $damaged = $event->getEntity();

        // pvp flag
        if ($damaged instanceof Player) {
            $plot = Plot::fromPosition($damaged->getPosition());
            if ($plot !== null) {
                if ($damager->hasPermission("cplot.pvp.plot")) return;
                $plot->loadFlags();
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVP);
                if ($flag !== null && $flag->getValue() === true) return;

            } else {
                if ($damager->hasPermission("cplot.pvp.road")) return;
            }

        // pve flag
        } else {
            $plot = Plot::fromPosition($damaged->getPosition());
            if ($plot !== null) {
                $plot->loadFlags();
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVE);
                if ($flag !== null && $flag->getValue() === true) return;
            }
        }

        $event->cancel();
    }
}