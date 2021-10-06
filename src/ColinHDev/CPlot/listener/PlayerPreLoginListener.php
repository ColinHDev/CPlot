<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\players\PlayerData;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;

class PlayerPreLoginListener implements Listener {

    public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void {
        if ($event->isCancelled()) return;

        $playerInfo = $event->getPlayerInfo();
        $player = new PlayerData(
            $playerInfo->getUuid()->toString(),
            $playerInfo->getUsername(),
            (int) (round(microtime(true) * 1000))
        );
        if (CPlot::getInstance()->getProvider()->setPlayerData($player)) return;

        $event->setKickReason(
            PlayerPreLoginEvent::KICK_REASON_PLUGIN,
            ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.prelogin.savePlayerDataError")
        );
    }
}