<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use Ramsey\Uuid\Uuid;

class BlockBreakListener implements Listener {

    public function onBlockBreak(BlockBreakEvent $event) : void {
        if ($event->isCancelled()) return;

        $position = $event->getBlock()->getPosition();
        if (CPlot::getInstance()->getProvider()->getWorld($position->getWorld()->getFolderName()) === null) return;

        $player = $event->getPlayer();

        $plot = Plot::fromPosition($position);
        if ($plot !== null) {
            if ($player->hasPermission("cplot.break.plot")) return;

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
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_BREAK);
            if ($flag !== null) {
                $value = $flag->getValueNonNull();
                if (is_array($value)) {
                    // TODO flag value should be reparsed to ensure backwards compatibility on block name changes
                    if (array_search($block->getName(), $value, true) !== false) return;
                }
            }

        } else {
            if ($player->hasPermission("cplot.break.road")) return;
        }

        $event->cancel();
    }
}