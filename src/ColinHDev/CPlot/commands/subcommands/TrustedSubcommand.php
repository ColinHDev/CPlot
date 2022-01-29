<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<null>
 */
class TrustedSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.senderNotOnline"));
            return;
        }

        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noPlotWorld"));
            return;
        }
        $plot = yield from Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noPlot"));
            return;
        }

        $trustedPlayerData = [];
        foreach ($plot->getPlotTrusted() as $plotPlayer) {
            $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByUUID($plotPlayer->getPlayerUUID());
            if ($playerData instanceof PlayerData) {
                $playerName = $playerData->getPlayerName();
            } else {
                $playerName = "ERROR";
            }
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $trustedPlayerData[] = $this->translateString("trusted.success.list", [
                $playerName,
                $this->translateString("trusted.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($trustedPlayerData) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noTrustedPlayers"));
            return;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "trusted.success",
                [
                    implode($this->translateString("trusted.success.list.separator"), $trustedPlayerData)
                ]
            )
        );
    }
}