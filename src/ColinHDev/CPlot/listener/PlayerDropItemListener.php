<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
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
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($position->getWorld()->getFolderName());
        if ($worldSettings === null) {
            LanguageManager::getInstance()->getProvider()->sendMessage($player, ["prefix", "player.interact.worldNotLoaded"]);
            $event->cancel();
            return;
        }
        if (!$worldSettings instanceof WorldSettings) {
            return;
        }

        $plot = Plot::loadFromPositionIntoCache($position);
        if ($plot instanceof BasePlot && !$plot instanceof Plot) {
            LanguageManager::getInstance()->getProvider()->sendMessage($player, ["prefix", "player.interact.plotNotLoaded"]);
            $event->cancel();
            return;
        }
        if ($plot instanceof Plot) {
            if ($player->hasPermission("cplot.interact.plot")) {
                return;
            }

            if ($plot->isPlotOwner($player)) {
                return;
            }
            if ($plot->isPlotTrusted($player)) {
                return;
            }
            if ($plot->isPlotHelper($player)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner !== null) {
                        return;
                    }
                }
            }

            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_ITEM_DROP);
            if ($flag->getValue() === true) {
                return;
            }

        } else {
            if ($player->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}