<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
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
        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.loadPlotPlayersError"));
            return;
        }

        $trustedPlayers = [];
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($plotPlayer->getState() !== PlotPlayer::STATE_TRUSTED) continue;
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $trustedPlayers[] = $this->translateString("trusted.success.list", [
                $this->getPlugin()->getProvider()->getPlayerDataByUUID($plotPlayer->getPlayerUUID())?->getPlayerName() ?? "ERROR",
                $this->translateString("trusted.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($trustedPlayers) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trusted.noTrustedPlayers"));
            return;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "trusted.success",
                [
                    implode($this->translateString("trusted.success.list.separator"), $trustedPlayers)
                ]
            )
        );
    }
}