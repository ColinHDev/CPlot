<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use Ramsey\Uuid\Uuid;

class BlockPlaceListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event) : void {
        if ($event->isCancelled()) return;

        $player = $event->getPlayer();

        $plot = Plot::fromPosition($event->getBlock()->getPos());
        if ($plot !== null) {
            if ($player->hasPermission("cplot.place.plot")) return;

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
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PLACE);
            if ($flag !== null) {
                $value = $flag->getValueNonNull();
                if (is_array($value)) {
                    if (array_search($block->getFullId(), $value, true) !== false) return;
                }
            }

        } else {
            if ($player->hasPermission("cplot.place.road")) return;
        }

        $event->cancel();
    }
}