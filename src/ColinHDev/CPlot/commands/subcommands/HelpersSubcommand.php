<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
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

        try {
            $helpers = $plot->getPlotHelpers();
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("helpers.loadPlotPlayersError"));
            return;
        }

        $helperData = [];
        foreach ($helpers as $plotPlayer) {
            [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotPlayer->getAddTime() / 1000))));
            $helperData[] = $this->translateString("helpers.success.list", [
                $this->getPlugin()->getProvider()->getPlayerDataByUUID($plotPlayer->getPlayerUUID())?->getPlayerName() ?? "ERROR",
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