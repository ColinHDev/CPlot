<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
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

        if (!((DataProvider::getInstance()->loadWorldIntoCache($damaged->getWorld()->getFolderName())) instanceof WorldSettings)) {
            return;
        }
        $plot = Plot::loadFromPositionIntoCache($damaged->getPosition());
        if ($plot instanceof BasePlot && !($plot instanceof Plot)) {
            $event->cancel();
            return;
        }
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

            } else {
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