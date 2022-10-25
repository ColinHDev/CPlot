<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TrustedSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "trusted.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "trusted.noPlotWorld"]);
            return;
        }
        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "trusted.noPlot"]);
            return;
        }

        $trustedPlayerData = [];
        foreach ($plot->getPlotTrusted() as $plotPlayer) {
            $plotPlayerData = $plotPlayer->getPlayerData();
            /** @phpstan-var string $addTime */
            $addTime = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $sender,
                ["trusted.success.list.addTime.format" => explode(".", date("d.m.Y.H.i.s", $plotPlayer->getAddTime()))]
            );
            $trustedPlayerData[] = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $sender,
                ["trusted.success.list" => [
                    $plotPlayerData->getPlayerName() ?? "Error: " . ($plotPlayerData->getPlayerXUID() ?? $plotPlayerData->getPlayerUUID() ?? $plotPlayerData->getPlayerID()),
                    $addTime
                ]]
            );
        }
        if (count($trustedPlayerData) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "trusted.noTrustedPlayers"]);
            return;
        }

        /** @phpstan-var string $separator */
        $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "trusted.success.list.separator");
        $list = implode($separator, $trustedPlayerData);
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
            $sender,
            [
                "prefix",
                "trusted.success" => $list
            ]
        );
    }
}