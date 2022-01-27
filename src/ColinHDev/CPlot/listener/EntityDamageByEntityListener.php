<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
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

        $plot = Plot::loadFromPositionIntoCache($damaged->getPosition());
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            $damager->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.interact.plotNotLoaded"));
            $event->cancel();
            return;
        }
        // pvp flag
        if ($damaged instanceof Player) {
            if ($plot !== null) {
                if ($damager->hasPermission("cplot.pvp.plot")) {
                    return;
                }
                /** @var BooleanAttribute $flag */
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
            if ($plot !== null) {
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