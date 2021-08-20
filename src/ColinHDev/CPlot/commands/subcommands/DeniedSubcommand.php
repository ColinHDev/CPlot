<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class DeniedSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.senderNotOnline"));
            return;
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noPlotWorld"));
            return;
        }
        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noPlot"));
            return;
        }
        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.loadPlotPlayersError"));
            return;
        }

        $trustedPlayers = [];
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($plotPlayer->getState() !== PlotPlayer::STATE_DENIED) continue;
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $trustedPlayers[] = $this->translateString("denied.success.list", [
                $this->getPlugin()->getProvider()->getPlayerByUUID($plotPlayer->getPlayerUUID())?->getPlayerName() ?? "ERROR",
                $this->translateString("denied.success.list.addTime.format", [$d, $m, $y, $h, $min, $s])
            ]);
        }
        if (count($trustedPlayers) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("denied.noDeniedPlayers"));
            return;
        }

        $sender->sendMessage(
            $this->getPrefix() .
            $this->translateString(
                "denied.success",
                [
                    implode($this->translateString("denied.success.list.separator"), $trustedPlayers)
                ]
            )
        );
    }
}