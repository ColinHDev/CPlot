<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\players\PlayerData;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;

class PlayerLoginListener implements Listener {

    public function onPlayerLogin(PlayerLoginEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $playerInfo = $event->getPlayer();
        $player = new PlayerData(
            $playerInfo->getUuid()->toString(),
            $playerInfo->getUsername(),
            (int) (round(microtime(true) * 1000))
        );
        if (CPlot::getInstance()->getProvider()->setPlayerData($player)) {
            return;
        }

        $event->cancel();
        $event->setKickMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.login.savePlayerDataError"));
    }
}