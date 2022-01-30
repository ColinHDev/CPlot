<?php

declare(strict_types=1);

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
class DeniedSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.senderNotOnline"));
            return null;
        }

        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noPlotWorld"));
            return null;
        }
        $plot = yield from Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noPlot"));
            return null;
        }

        $deniedPlayerData = [];
        foreach ($plot->getPlotDenied() as $plotPlayer) {
            $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByUUID($plotPlayer->getPlayerUUID());
            if ($playerData instanceof PlayerData) {
                $playerName = $playerData->getPlayerName();
            } else {
                $playerName = "ERROR";
            }
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $deniedPlayerData[] = $this->translateString("denied.success.list", [
                $playerName,
                $this->translateString("denied.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($deniedPlayerData) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noDeniedPlayers"));
            return null;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "denied.success",
                [
                    implode($this->translateString("denied.success.list.separator"), $deniedPlayerData)
                ]
            )
        );
        return null;
    }
}