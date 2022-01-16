<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;

class PlayerLoginListener implements Listener {

    public function onPlayerLogin(PlayerLoginEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        Await::g2c(
            DataProvider::getInstance()->updatePlayerData(
                $player->getUniqueId()->toString(),
                $player->getName()
            ),
            null,
            static function (SqlError $error) use ($player) : void {
                if ($player->isConnected()) {
                    $player->kick(
                        ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("player.login.savePlayerDataError")
                    );
                }
            }
        );
    }
}