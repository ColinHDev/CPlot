<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use SOFe\AwaitGenerator\Await;

class PlayerLoginListener implements Listener {

    public function onPlayerLogin(PlayerLoginEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        Await::g2c(
            DataProvider::getInstance()->updatePlayerData(
                $player->getUniqueId()->getBytes(),
                $player->getName()
            ),
            null,
            static function (\Throwable $error) use ($player) : void {
                if ($player->isConnected()) {
                    $player->kick(
                        LanguageManager::getInstance()->getProvider()->translateString(["prefix", "player.login.savePlayerDataError"])
                    );
                }
            }
        );
    }
}