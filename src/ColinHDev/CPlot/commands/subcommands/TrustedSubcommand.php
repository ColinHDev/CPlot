<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TrustedSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.senderNotOnline"));
            return;
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noPlotWorld"));
            return;
        }
        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noPlot"));
            return;
        }

        try {
            $trustedPlayers = $plot->getPlotTrusted();
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.loadPlotPlayersError"));
            return;
        }

        $trustedPlayerData = [];
        foreach ($trustedPlayers as $plotPlayer) {
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $trustedPlayerData[] = $this->translateString("trusted.success.list", [
                $this->getPlugin()->getProvider()->getPlayerDataByUUID($plotPlayer->getPlayerUUID())?->getPlayerName() ?? "ERROR",
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