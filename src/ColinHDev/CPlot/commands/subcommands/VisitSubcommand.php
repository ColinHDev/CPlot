<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class VisitSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.senderNotOnline"));
            return;
        }

        switch (count($args)) {
            case 0:
                /** @var Plot[] | null $plots */
                $plots = $this->getPlugin()->getProvider()->getPlotsByPlotPlayer(new PlotPlayer($sender->getUniqueId()->toString(), PlotPlayer::STATE_OWNER));
                if ($plots === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.loadPlotsError"));
                    return;
                }
                if (count($plots) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.noPlots"));
                    return;
                }
                /** @var Plot $plot */
                $plot = $plots[0];
                try {
                    $plot->teleportTo($sender);
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                    return;
                }
                break;

            case 1:
                if (is_numeric($args[0])) {
                    /** @var Plot[] | null $plots */
                    $plots = $this->getPlugin()->getProvider()->getPlotsByPlotPlayer(new PlotPlayer($sender->getUniqueId()->toString(), PlotPlayer::STATE_OWNER));
                    if ($plots === null) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.loadPlotsError"));
                        return;
                    }
                    if (count($plots) === 0) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.noPlots"));
                        return;
                    }
                    $plotNumber = (int) $args[0];
                    if ($plotNumber > count($plots)) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.noPlot", [$plotNumber]));
                        return;
                    }
                    /** @var Plot $plot */
                    $plot = $plots[($plotNumber - 1)];
                    try {
                        $plot->teleportTo($sender);
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.success", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                    } catch (PlotException) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.teleportError", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                        return;
                    }
                    break;

                } else {
                    $player = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);
                    if ($player instanceof Player) {
                        $playerUUID = $player->getUniqueId()->toString();
                        $playerName = $player->getName();
                    } else {
                        $playerName = $args[0];
                        $playerData = $this->getPlugin()->getProvider()->getPlayerDataByName($playerName);
                        $playerUUID = $playerData?->getPlayerUUID();
                    }

                    if ($playerUUID !== null) {
                        /** @var Plot[] | null $plots */
                        $plots = $this->getPlugin()->getProvider()->getPlotsByPlotPlayer(new PlotPlayer($playerUUID, PlotPlayer::STATE_OWNER));
                        if ($plots === null) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.loadPlotsError"));
                            return;
                        }
                        if (count($plots) === 0) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.noPlots", [$playerName]));
                            return;
                        }
                        /** @var Plot $plot */
                        $plot = $plots[0];
                        try {
                            $plot->teleportTo($sender);
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                        } catch (PlotException) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                            return;
                        }
                        break;

                    } else {
                        $alias = strtolower($args[0]);
                        /** @var Plot $plots */
                        $plot = $this->getPlugin()->getProvider()->getPlotByAlias($alias);
                        if ($plot === null) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.noPlot", [$alias]));
                            break;
                        }
                        try {
                            $plot->teleportTo($sender);
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]));
                        } catch (PlotException) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]));
                            return;
                        }
                        break;
                    }
                }

            default:
                $player = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);
                if ($player instanceof Player) {
                    $playerUUID = $player->getUniqueId()->toString();
                    $playerName = $player->getName();
                } else {
                    $playerName = $args[0];
                    $playerData = $this->getPlugin()->getProvider()->getPlayerDataByName($playerName);
                    if ($playerData === null) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.playerNotFound", [$playerName]));
                        return;
                    }
                    $playerUUID = $playerData->getPlayerUUID();
                }

                /** @var Plot[] | null $plots */
                $plots = $this->getPlugin()->getProvider()->getPlotsByPlotPlayer(new PlotPlayer($playerUUID, PlotPlayer::STATE_OWNER));
                if ($plots === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.loadPlotsError"));
                    return;
                }
                if (count($plots) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.noPlots", [$playerName]));
                    return;
                }
                $plotNumber = (int) $args[1];
                if ($plotNumber > count($plots)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.noPlot", [$playerName, $plotNumber]));
                    return;
                }
                /** @var Plot $plot */
                $plot = $plots[($plotNumber - 1)];
                try {
                    $plot->teleportTo($sender);
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.success", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.teleportError", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                    return;
                }
                break;
        }
    }
}