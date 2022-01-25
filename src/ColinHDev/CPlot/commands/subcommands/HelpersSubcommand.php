<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\players\PlayerData;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HelpersSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.senderNotOnline"));
            return;
        }

        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noPlotWorld"));
            return;
        }
        $plot = yield from Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noPlot"));
            return;
        }

        $helperData = [];
        foreach ($plot->getPlotHelpers() as $plotPlayer) {
            $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByUUID($plotPlayer->getPlayerUUID());
            if ($playerData instanceof PlayerData) {
                $playerName = $playerData->getPlayerName();
            } else {
                $playerName = "ERROR";
            }
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $helperData[] = $this->translateString("helpers.success.list", [
                $playerName,
                $this->translateString("helpers.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($helperData) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noHelpers"));
            return;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "helpers.success",
                [
                    implode($this->translateString("helpers.success.list.separator"), $helperData)
                ]
            )
        );
    }
}