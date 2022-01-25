<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\attributes\BlockListAttribute;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use Ramsey\Uuid\Uuid;

class BlockBreakListener implements Listener {

    public function onBlockBreak(BlockBreakEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $position = $event->getBlock()->getPosition();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $event->getPlayer()->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.break.worldNotLoaded"));
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            $event->getPlayer()->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.break.plotNotLoaded"));
            $event->cancel();
            return;
        }
        if ($plot !== null) {
            $player = $event->getPlayer();
            if ($player->hasPermission("cplot.break.plot")) {
                return;
            }

            $playerUUID = $player->getUniqueId()->getBytes();
            if ($plot->isPlotOwner($playerUUID)) {
                return;
            }
            if ($plot->isPlotTrusted($playerUUID)) {
                return;
            }
            if ($plot->isPlotHelper($playerUUID)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $player->getServer()->getPlayerByUUID(Uuid::fromBytes($plotOwner->getPlayerUUID()));
                    if ($owner !== null) {
                        return;
                    }
                }
            }

            $block = $event->getBlock();
            /** @var BlockListAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_BREAK);
            /** @var Block $value */
            foreach ($flag->getValue() as $value) {
                if ($block->isSameType($value)) {
                    return;
                }
            }

        } else {
            if ($event->getPlayer()->hasPermission("cplot.break.road")) {
                return;
            }
        }

        $event->cancel();
    }
}