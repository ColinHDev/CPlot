<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\flags\implementations\PlaceFlag;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\block\Block;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use Ramsey\Uuid\Uuid;

class BlockPlaceListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event) : void {
        if ($event->isCancelled()) return;

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) return;

        $player = $event->getPlayer();

        $plot = Plot::fromPosition($position);
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
            /** @var PlaceFlag | null $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_PLACE);
            if ($flag !== null) {
                /** @var Block $value */
                foreach ($flag->getValue() as $value) {
                    if ($block->isSameType($value)) {
                        return;
                    }
                }
            }

        } else {
            if ($player->hasPermission("cplot.place.road")) return;
        }

        $event->cancel();
    }
}