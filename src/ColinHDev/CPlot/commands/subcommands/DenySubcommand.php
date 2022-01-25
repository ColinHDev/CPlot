<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\attributes\BooleanAttribute;
use ColinHDev\CPlotAPI\players\PlayerData;
use ColinHDev\CPlotAPI\players\settings\SettingIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\PlotPlayer;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;

class DenySubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.senderNotOnline"));
            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }

        $player = null;
        if ($args[0] !== "*") {
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerName = $player->getName();
            } else {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerNotFound", [$playerName]));
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.senderIsPlayer"));
                return;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlotWorld"));
            return;
        }

        $plot = yield from Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlot"));
            return;
        }

        if (!$plot->hasPlotOwner()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.deny")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("deny.notPlotOwner"));
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.serverPlotFlag", [$flag->getID()]));
            return;
        }

        if ($plot->isPlotDeniedExact($playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("deny.playerAlreadyDenied", [$playerName]));
            return;
        }

        $plotPlayer = new PlotPlayer($playerUUID, PlotPlayer::STATE_DENIED);
        $plot->addPlotPlayer($plotPlayer);
        yield from DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        $sender->sendMessage($this->getPrefix() . $this->translateString("deny.success", [$playerName]));

        if ($player instanceof Player) {
            $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByUUID($playerUUID);
            if (!($playerData instanceof PlayerData)) {
                return;
            }
            /** @var BooleanAttribute $setting */
            $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_DENIED_ADD);
            if ($setting->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("deny.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            }
        }
    }

    /**
     * @param SqlError $error
     */
    public function onError(CommandSender $sender, mixed $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("deny.saveError", [$error->getMessage()]));
    }
}