<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class HelpersSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.senderNotOnline"));
            return;
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noPlotWorld"));
            return;
        }
        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noPlot"));
            return;
        }
        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.loadPlotPlayersError"));
            return;
        }

        $trustedPlayers = [];
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($plotPlayer->getState() !== PlotPlayer::STATE_HELPER) continue;
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $trustedPlayers[] = $this->translateString("helpers.success.list", [
                $this->getPlugin()->getProvider()->getPlayerDataByUUID($plotPlayer->getPlayerUUID())?->getPlayerName() ?? "ERROR",
                $this->translateString("helpers.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($trustedPlayers) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.noHelpers"));
            return;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "helpers.success",
                [
                    implode($this->translateString("helpers.success.list.separator"), $trustedPlayers)
                ]
            )
        );
    }
}