<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;

class PlayerPreLoginListener implements Listener {

    public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void {
        if ($event->isCancelled()) return;

        $playerInfo = $event->getPlayerInfo();
        if (CPlot::getInstance()->getProvider()->setPlayer($playerInfo->getUuid()->toString(), $playerInfo->getUsername())) return;

        $event->setKickReason(
            PlayerPreLoginEvent::KICK_REASON_PLUGIN,
            ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.prelogin.savePlayerDataError")
        );
    }
}