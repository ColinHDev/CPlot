<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\players\settings\SettingIDs;
use ColinHDev\CPlotAPI\players\utils\PlayerDataException;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AddSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.senderNotOnline"));
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
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerDataByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.senderIsPlayer"));
                return;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlot"));
            return;
        }

        try {
            if ($plot->hasPlotOwner() === null) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlotOwner"));
                return;
            }
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.loadPlotPlayersError"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.add")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.notPlotOwner"));
                return;
            }
        }

        try {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.loadFlagsError"));
            return;
        }
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.serverPlotFlag", [$flag->getID()]));
            return;
        }

        if ($plot->isPlotHelperExact($playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerAlreadyHelper", [$playerName]));
            return;
        }

        $plotPlayer = new PlotPlayer($playerUUID, PlotPlayer::STATE_HELPER);
        $plot->addPlotPlayer($plotPlayer);
        if (!$this->getPlugin()->getProvider()->savePlotPlayer($plot, $plotPlayer)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("add.success", [$playerName]));

        if ($player instanceof Player) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerDataByUUID($playerUUID);
            if ($playerData === null) {
                return;
            }
            try {
                $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_HELPER_ADD);
            } catch (PlayerDataException) {
                return;
            }
            if ($setting->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("add.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            }
        }
    }
}