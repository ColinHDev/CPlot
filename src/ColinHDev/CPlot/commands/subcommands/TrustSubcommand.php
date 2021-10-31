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

class TrustSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.senderNotOnline"));
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
                $sender->sendMessage($this->getPrefix() . $this->translateString("trust.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = $this->getPlugin()->getProvider()->getPlayerDataByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("trust.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("trust.senderIsPlayer"));
                return;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.noPlot"));
            return;
        }

        try {
            if ($plot->hasPlotOwner() === null) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("trust.noPlotOwner"));
                return;
            }
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.loadPlotPlayersError"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.trust")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("trust.notPlotOwner"));
                return;
            }
        }

        try {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.loadFlagsError"));
            return;
        }
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.serverPlotFlag", [$flag->getID()]));
            return;
        }

        if ($plot->isPlotTrustedExact($playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.playerAlreadyTrusted", [$playerName]));
            return;
        }

        $plotPlayer = new PlotPlayer($playerUUID, PlotPlayer::STATE_TRUSTED);
        $plot->addPlotPlayer($plotPlayer);
        if (!$this->getPlugin()->getProvider()->savePlotPlayer($plot, $plotPlayer)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("trust.saveError"));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("trust.success", [$playerName]));

        if ($player instanceof Player) {
            $playerData = $this->getPlugin()->getProvider()->getPlayerDataByUUID($playerUUID);
            if ($playerData === null) {
                return;
            }
            try {
                $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_TRUSTED_ADD);
            } catch (PlayerDataException) {
                return;
            }
            if ($setting->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("trust.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            }
        }
    }
}