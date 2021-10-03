<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\flags\FlagManager;
use ColinHDev\CPlotAPI\players\SettingIDs;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class DenySubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.senderNotOnline"));
            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }
        $player = null;
        $playerData = null;
        if ($args[0] !== "*") {
            $player = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->toString();
                $playerName = $player->getName();
            } else {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.senderIsPlayer"));
                return;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlot"));
            return;
        }

        if ($plot->getOwnerUUID() === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.deny")) {
            if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
        }

        if (!$plot->loadFlags()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.loadFlagsError"));
            return;
        }
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.serverPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.loadPlotPlayersError"));
            return;
        }
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($playerUUID !== $plotPlayer->getPlayerUUID()) continue;
            if ($plotPlayer->getState() !== PlotPlayer::STATE_DENIED) break;
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerAlreadyDenied", [$playerName]));
            return;
        }

        $plotPlayer = new PlotPlayer($playerUUID, PlotPlayer::STATE_DENIED);
        if (!$this->getPlugin()->getProvider()->savePlotPlayer($plot, $plotPlayer)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("deny.success", [$playerName]));

        if ($playerUUID === "*") return;
        if ($player === null) return;
        if ($playerData === null) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
        }
        if (!$playerData->loadSettings()) return;
        $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_DENIED_ADD);
        if ($setting === null || $setting->getValueNonNull() === false) return;
        $player->sendMessage($this->getPrefix() . $this->translateString("deny.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}