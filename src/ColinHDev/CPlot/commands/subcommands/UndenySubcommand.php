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

class UndenySubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.senderNotOnline"));
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
                $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.noPlot"));
            return;
        }

        if ($plot->getOwnerUUID() === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.undeny")) {
            if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
        }

        if (!$plot->loadFlags()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.loadFlagsError"));
            return;
        }
        $flag = $plot->getFlagByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag === null) {
            $value = FlagManager::getInstance()->getFlagByID(FlagIDs::FLAG_SERVER_PLOT)?->getParsedDefault();
        } else {
            $value = $flag->getValue();
        }
        if ($value === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.serverPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        if (!$plot->loadPlotPlayers()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.loadPlotPlayersError"));
            return;
        }
        foreach ($plot->getPlotPlayers() as $plotPlayer) {
            if ($playerUUID !== $plotPlayer->getPlayerUUID()) continue;
            if ($plotPlayer->getState() === PlotPlayer::STATE_DENIED) break;
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.playerAlreadyDenied", [$playerName]));
            return;
        }

        if (!$this->getPlugin()->getProvider()->deletePlotPlayer($plot, $playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.success", [$playerName]));

        if ($playerUUID === "*") return;
        if ($player === null) return;
        if ($playerData === null) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerByName($playerName);
        }
        if (!$playerData->loadSettings()) return;
        $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_DENIED_REMOVE);
        if ($setting === null || $setting->getValueNonNull() === false) return;
        $player->sendMessage($this->getPrefix() . $this->translateString("undeny.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}