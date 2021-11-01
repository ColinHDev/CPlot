<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\UseFlag;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\block\Block;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use Ramsey\Uuid\Uuid;

class PlayerInteractListener implements Listener {

    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) {
            return;
        }

        $player = $event->getPlayer();

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
                $block = $event->getBlock();

                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PLAYER_INTERACT);
                if ($flag->getValue() === true) {
                    if ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) {
                        return;
                    }
                }

                /** @var UseFlag | null $flag */
                $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_USE);
                /** @var Block $value */
                foreach ($flag->getValue() as $value) {
                    if ($block->isSameType($value)) {
                        return;
                    }
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