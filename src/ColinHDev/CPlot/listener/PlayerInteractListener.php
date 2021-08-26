<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use Ramsey\Uuid\Uuid;

class PlayerInteractListener implements Listener {

    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        if ($event->isCancelled()) return;

        $player = $event->getPlayer();

        $plot = Plot::fromPosition($event->getBlock()->getPosition());
        if ($plot !== null) {
            if ($player->hasPermission("cplot.interact.plot")) return;

            $ownerUUID = $plot->getOwnerUUID();
            $playerUUID = $player->getUniqueId()->toString();
            if ($ownerUUID === $playerUUID) return;

            $plot->loadPlotPlayers();
            $plotPlayer = $plot->getPlotPlayer($playerUUID);
            if ($plotPlayer !== null) {
                $state = $plotPlayer->getState();
                switch ($state) {
                    case PlotPlayer::STATE_TRUSTED:
                        return;
                    case PlotPlayer::STATE_HELPER:
                        if ($ownerUUID !== null) {
                            $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($ownerUUID));
                            if ($owner !== null) return;
                        }
                }
            }

            $block = $event->getBlock();

            $plot->loadFlags();
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PLAYER_INTERACT);
            if ($flag !== null && $flag->getValueNonNull() === true) {
                if ($block instanceof Door) return;
                if ($block instanceof Trapdoor) return;
                if ($block instanceof FenceGate) return;
            }
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_USE);
            if ($flag !== null) {
                $value = $flag->getValueNonNull();
                if (is_array($value)) {
                    if (array_search($block->getFullId(), $value, true) !== false) return;
                }
            }

        } else {
            if ($player->hasPermission("cplot.interact.road")) return;
        }

        $event->cancel();
    }
}