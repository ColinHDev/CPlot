<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\players\settings\SettingIDs;
use ColinHDev\CPlotAPI\players\utils\PlayerDataException;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
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
        if ($args[0] !== "*") {
            $player = $this->getPlugin()->getServer()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->toString();
                $playerName = $player->getName();
            } else {
                $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerDataByName($playerName);
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

        try {
            if (!$plot->hasPlotOwner()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.noPlotOwner"));
                return;
            }
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.loadPlotPlayersError"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.undeny")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.notPlotOwner"));
                return;
            }
        }

        try {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.loadFlagsError"));
            return;
        }
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.serverPlotFlag", [$flag->getID()]));
            return;
        }

        if (!$plot->isPlotDeniedExact($playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.playerNotDenied", [$playerName]));
            return;
        }

        $plot->removePlotPlayer($playerUUID);
        if (!$this->getPlugin()->getProvider()->deletePlotPlayer($plot, $playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("undeny.success", [$playerName]));

        if ($player instanceof Player) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerDataByUUID($playerUUID);
            if ($playerData === null) {
                return;
            }
            try {
                $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_DENIED_REMOVE);
            } catch (PlayerDataException) {
                return;
            }
            if ($setting->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("undeny.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            }
        }
    }
}