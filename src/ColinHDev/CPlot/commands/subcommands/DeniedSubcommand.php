<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class DeniedSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "denied.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "denied.noPlotWorld"]);
            return;
        }
        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "denied.noPlot"]);
            return;
        }

        $deniedPlayerData = [];
        foreach ($plot->getPlotDenied() as $plotPlayer) {
            $plotPlayerData = $plotPlayer->getPlayerData();
            /** @phpstan-var string $addTime */
            $addTime = self::translateForCommandSender(
                $sender,
                ["denied.success.list.addTime.format" => explode(".", date("d.m.Y.H.i.s", $plotPlayer->getAddTime()))]
            );
            $deniedPlayerData[] = self::translateForCommandSender(
                $sender,
                ["denied.success.list" => [
                    $plotPlayerData->getPlayerName() ?? "Error: " . ($plotPlayerData->getPlayerXUID() ?? $plotPlayerData->getPlayerUUID() ?? $plotPlayerData->getPlayerID()),
                    $addTime
                ]]
            );
        }
        if (count($deniedPlayerData) === 0) {
            self::sendMessage($sender, ["prefix", "denied.noDeniedPlayers"]);
            return;
        }

        /** @phpstan-var string $separator */
        $separator = self::translateForCommandSender($sender, "denied.success.list.separator");
        $list = implode($separator, $deniedPlayerData);
        self::sendMessage(
            $sender,
            [
                "prefix",
                "denied.success" => $list
            ]
        );
    }
}