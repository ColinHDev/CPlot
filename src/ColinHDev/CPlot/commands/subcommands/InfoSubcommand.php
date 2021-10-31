<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class InfoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("info.senderNotOnline"));
            return;
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("info.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("info.noPlot"));
            return;
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("info.plot", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));

        try {
            $plotOwnerData = [];
            foreach ($plot->getPlotOwners() as $plotOwner) {
                [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plotOwner->getAddTime() / 1000))));
                $plotOwnerData[] = $this->translateString("info.owners.list", [
                    $this->getPlugin()->getProvider()->getPlayerDataByUUID($plotOwner->getPlayerUUID())?->getPlayerName() ?? "ERROR",
                    $this->translateString("info.owners.time.format", [$d, $m, $y, $h, $min, $s])
                ]);
            }
            if (count($plotOwnerData) === 0) {
                $sender->sendMessage($this->translateString("info.owners.none"));
            } else {
                $sender->sendMessage(
                    $this->translateString(
                        "info.owners",
                        [
                            implode($this->translateString("info.owners.list.separator"), $plotOwnerData)
                        ]
                    )
                );
            }
        } catch (PlotException) {
            $sender->sendMessage($this->translateString("info.loadPlotPlayersError"));
        }

        if ($plot->getAlias() !== null) {
            $sender->sendMessage($this->translateString("info.plotAlias", [$plot->getAlias()]));
        } else {
            $sender->sendMessage($this->translateString("info.plotAlias.none"));
        }

        try {
            $mergedPlotsCount = count($plot->getMergePlots());
            if ($mergedPlotsCount > 0) {
                $sender->sendMessage($this->translateString("info.merges", [$mergedPlotsCount]));
            } else {
                $sender->sendMessage($this->translateString("info.merges.none"));
            }
        } catch (PlotException) {
            $sender->sendMessage($this->translateString("info.loadMergedPlotsError"));
        }

        try {
            $trustedCount = count($plot->getPlotTrusted());
            if ($trustedCount > 0) {
                $sender->sendMessage($this->translateString("info.trusted", [$trustedCount]));
            } else {
                $sender->sendMessage($this->translateString("info.trusted.none"));
            }
            $helpersCount = count($plot->getPlotHelpers());
            if ($helpersCount > 0) {
                $sender->sendMessage($this->translateString("info.helpers", [$helpersCount]));
            } else {
                $sender->sendMessage($this->translateString("info.helpers.none"));
            }
            $deniedCount = count($plot->getPlotDenied());
            if ($deniedCount > 0) {
                $sender->sendMessage($this->translateString("info.denied", [$deniedCount]));
            } else {
                $sender->sendMessage($this->translateString("info.denied.none"));
            }
        } catch (PlotException) {
            $sender->sendMessage($this->translateString("info.loadPlotPlayersError"));
        }

        try {
            $flagsCount = count($plot->getFlags());
            if ($flagsCount > 0) {
                $sender->sendMessage($this->translateString("info.flags", [$flagsCount]));
            } else {
                $sender->sendMessage($this->translateString("info.flags.none"));
            }
        } catch (PlotException) {
            $sender->sendMessage($this->translateString("info.loadFlagsError"));
        }

        try {
            $ratesCount = count($plot->getPlotRates());
            if ($ratesCount > 0) {
                $sender->sendMessage($this->translateString("info.rates", [$ratesCount]));
            } else {
                $sender->sendMessage($this->translateString("info.rates.none"));
            }
        } catch (PlotException) {
            $sender->sendMessage($this->translateString("info.loadRatesError"));
        }
    }
}