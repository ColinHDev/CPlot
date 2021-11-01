<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use Ramsey\Uuid\Uuid;

class PlayerDropItemListener implements Listener {

    public function onPlayerDropItem(PlayerDropItemEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        $position = $player->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) {
            return;
        }

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            if ($player->hasPermission("cplot.interact.plot")) {
                return;
            }

            try {
                $playerUUID = $player->getUniqueId()->toString();
                if ($plot->isPlotOwner($playerUUID)) {
                    return;
                }
                if ($plot->isPlotTrusted($playerUUID)) {
                    return;
                }
                if ($plot->isPlotHelper($playerUUID)) {
                    foreach ($plot->getPlotOwners() as $plotOwner) {
                        $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($plotOwner->getPlayerUUID()));
                        if ($owner !== null) {
                            return;
                        }
                    }
                }
            } catch (PlotException) {
            }

            try {
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_ITEM_DROP);
                if ($flag->getValue() === true) {
                    return;
                }
            } catch (PlotException) {
            }

        } else {
            if ($player->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}