<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HelpersSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "helpers.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "helpers.noPlotWorld"]);
            return;
        }
        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "helpers.noPlot"]);
            return;
        }

        $helperData = [];
        foreach ($plot->getPlotHelpers() as $plotPlayer) {
            $plotPlayerData = $plotPlayer->getPlayerData();
            $addTime = self::translateForCommandSender(
                $sender,
                ["format.time" => explode(".", date("Y.m.d.H.i.s", $plotPlayer->getAddTime()))]
            );
            $helperData[] = self::translateForCommandSender(
                $sender,
                ["format.list.playerWithTime" => [
                    $plotPlayerData->getPlayerName() ?? "Unknown",
                    $addTime
                ]]
            );
        }
        if (count($helperData) === 0) {
            self::sendMessage($sender, ["prefix", "helpers.noHelpers"]);
            return;
        }

        /** @phpstan-var string $separator */
        $separator = self::translateForCommandSender($sender, "format.list.playerWithTime.separator");
        $list = implode($separator, $helperData);
        self::sendMessage(
            $sender,
            [
                "prefix",
                "helpers.success" => $list
            ]
        );
    }
}