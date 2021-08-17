<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
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

        $plot = Plot::fromPosition($sender->getPosition(), true);
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("info.noPlot"));
            return;
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("info.plot", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));

        if ($plot->getOwnerUUID() !== null) {
            if ($plot->getClaimTime() !== null) {
                [$d, $m, $y, $h, $min, $s] = explode(".", date("d.m.Y.H.i.s", (int) (round($plot->getClaimTime() / 1000))));
                $time = $this->translateString("info.owner.claimTime.format", [$d, $m, $y, $h, $min, $s]);
            } else {
                $time = "NOT FOUND";
            }
            $sender->sendMessage($this->translateString("info.owner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()), $time]));
        } else {
            $sender->sendMessage($this->translateString("info.owner.none"));
        }

        if ($plot->getAlias() !== null) {
            $sender->sendMessage($this->translateString("info.plotAlias", [$plot->getAlias()]));
        } else {
            $sender->sendMessage($this->translateString("info.plotAlias.none"));
        }

        if ($plot->loadMergedPlots()) {
            $mergedPlotsCount = count($plot->getMergedPlots());
            if ($mergedPlotsCount > 0) {
                $sender->sendMessage($this->translateString("info.merges", [$mergedPlotsCount]));
            } else {
                $sender->sendMessage($this->translateString("info.merges.none"));
            }
        } else {
            $sender->sendMessage($this->translateString("info.loadMergedPlotsError"));
        }

        if ($plot->loadPlotPlayers()) {
            $trustedCount = 0;
            $helpersCount = 0;
            $deniedCount = 0;
            foreach ($plot->getPlotPlayers() as $plotPlayer) {
                switch ($plotPlayer->getState()) {
                    case PlotPlayer::STATE_TRUSTED:
                        $trustedCount++;
                        break;
                    case PlotPlayer::STATE_HELPER:
                        $helpersCount++;
                        break;
                    case PlotPlayer::STATE_DENIED:
                        $deniedCount++;
                        break;
                }
            }
            if ($trustedCount > 0) {
                $sender->sendMessage($this->translateString("info.trusted", [$trustedCount]));
            } else {
                $sender->sendMessage($this->translateString("info.trusted.none"));
            }
            if ($helpersCount > 0) {
                $sender->sendMessage($this->translateString("info.helpers", [$helpersCount]));
            } else {
                $sender->sendMessage($this->translateString("info.helpers.none"));
            }
            if ($deniedCount > 0) {
                $sender->sendMessage($this->translateString("info.denied", [$deniedCount]));
            } else {
                $sender->sendMessage($this->translateString("info.denied.none"));
            }
        } else {
            $sender->sendMessage($this->translateString("info.loadFlagsError"));
        }

        if ($plot->loadFlags()) {
            $flagsCount = count($plot->getFlags());
            if ($flagsCount > 0) {
                $sender->sendMessage($this->translateString("info.flags", [$flagsCount]));
            } else {
                $sender->sendMessage($this->translateString("info.flags.none"));
            }
        } else {
            $sender->sendMessage($this->translateString("info.loadFlagsError"));
        }

        if ($plot->loadPlotRates()) {
            $ratesCount = count($plot->getPlotRates());
            if ($ratesCount > 0) {
                $sender->sendMessage($this->translateString("info.rates", [$ratesCount]));
            } else {
                $sender->sendMessage($this->translateString("info.rates.none"));
            }
        } else {
            $sender->sendMessage($this->translateString("info.loadRatesError"));
        }
    }
}