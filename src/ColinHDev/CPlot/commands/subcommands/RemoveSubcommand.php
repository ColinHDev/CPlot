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

class RemoveSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.senderNotOnline"));
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
                $sender->sendMessage($this->getPrefix() . $this->translateString("remove.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("remove.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.noPlot"));
            return;
        }

        if ($plot->getOwnerUUID() === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.remove")) {
            if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("remove.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
        }

        if (!$plot->loadFlags()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.loadFlagsError"));
            return;
        }
        $flag = $plot->getFlagByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag === null) {
            $value = FlagManager::getInstance()->getFlagByID(FlagIDs::FLAG_SERVER_PLOT)?->getParsedDefault();
        } else {
            $value = $flag->getValue();
        }
        if ($value === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.serverPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.loadPlotPlayersError"));
            return;
        }
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($playerUUID !== $plotPlayer->getPlayerUUID()) continue;
            if ($plotPlayer->getState() === PlotPlayer::STATE_HELPER) break;
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.playerNotHelper", [$playerName]));
            return;
        }

        if (!$this->getPlugin()->getProvider()->deletePlotPlayer($plot, $playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("remove.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("remove.success", [$playerName]));

        if ($playerUUID === "*") return;
        if ($player === null) return;
        if ($playerData === null) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
        }
        if (!$playerData->loadSettings()) return;
        $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_HELPER_REMOVE);
        if ($setting === null || $setting->getValueNonNull() === false) return;
        $player->sendMessage($this->getPrefix() . $this->translateString("remove.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}