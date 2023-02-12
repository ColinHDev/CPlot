<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use SOFe\AwaitGenerator\Await;

class PlayerLoginListener implements Listener {

    /**
     * @handleCancelled false
     */
    public function onPlayerLogin(PlayerLoginEvent $event) : void {
        $player = $event->getPlayer();
        Await::g2c(
            DataProvider::getInstance()->updatePlayerData(
                $player->getUniqueId()->getBytes(),
                $player->getXuid(),
                $player->getName()
            ),
            null,
            static function() use ($player) : void {
                if (!$player->isConnected()) {
                    return;
                }
                $player->kick(
                    LanguageManager::getInstance()->getProvider()->translateForCommandSender(
                        $player,
                        ["prefix", "player.login.savePlayerDataError"]
                    )
                );
            }
        );
    }
}