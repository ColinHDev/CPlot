<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\commands\CommandExecutor;
use ColinHDev\CPlot\commands\CommandExecutorManager;
use ColinHDev\CPlot\language\KnownTranslationFactory;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\provider\DataProvider;
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
            static function(?PlayerData $playerData) use($player) : void {
                if (!$player->isConnected()) {
                    return;
                }
                if ($playerData === null) {
                    $player->kick((new CommandExecutor($player))->translate(
                        KnownTranslationFactory::prefix(),
                        KnownTranslationFactory::playerLogin_savePlayerDataError()
                    ));
                    return;
                }
                CommandExecutorManager::getInstance()->registerPlayerSession($player, $playerData);
            },
            static function() use($player) : void {
                if (!$player->isConnected()) {
                    return;
                }
                $player->kick((new CommandExecutor($player))->translate(
                    KnownTranslationFactory::prefix(),
                    KnownTranslationFactory::playerLogin_savePlayerDataError()
                ));
            }
        );
    }
}