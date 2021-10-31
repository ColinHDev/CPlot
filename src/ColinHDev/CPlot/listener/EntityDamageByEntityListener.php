<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityDamageByEntityListener implements Listener {

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $damager = $event->getDamager();
        if (!$damager instanceof Player) {
            return;
        }
        $damaged = $event->getEntity();

        try {
            // pvp flag
            if ($damaged instanceof Player) {
                $plot = Plot::fromPosition($damaged->getPosition());
                if ($plot !== null) {
                    if ($damager->hasPermission("cplot.pvp.plot")) {
                        return;
                    }
                    $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVP);
                    if ($flag->getValue() === true) {
                        return;
                    }

                } else {
                    if ($damager->hasPermission("cplot.pvp.road")) {
                        return;
                    }
                }

            // pve flag
            } else {
                $plot = Plot::fromPosition($damaged->getPosition());
                if ($plot !== null) {
                    $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PVE);
                    if ($flag->getValue() === true) {
                        return;
                    }
                }
            }
        } catch (PlotException) {
        }

        $event->cancel();
    }
}