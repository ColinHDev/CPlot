<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<null>
 */
class AddSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.senderNotOnline"));
            return null;
        }

        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return null;
        }

        $player = null;
        if ($args[0] !== "*") {
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerName = $player->getName();
            } else {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerNotOnline", [$args[0]]));
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if ($playerData === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerNotFound", [$playerName]));
                    return null;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.senderIsPlayer"));
                return null;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlotWorld"));
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlot"));
            return null;
        }

        if (!$plot->hasPlotOwner()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.noPlotOwner"));
            return null;
        }
        if (!$sender->hasPermission("cplot.admin.add")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("add.notPlotOwner"));
                return null;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.serverPlotFlag", [$flag->getID()]));
            return null;
        }

        if ($plot->isPlotHelperExact($playerUUID)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("add.playerAlreadyHelper", [$playerName]));
            return null;
        }

        $plotPlayer = new PlotPlayer($playerUUID, PlotPlayer::STATE_HELPER);
        $plot->addPlotPlayer($plotPlayer);
        yield DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        $sender->sendMessage($this->getPrefix() . $this->translateString("add.success", [$playerName]));

        if ($player instanceof Player) {
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByUUID($playerUUID);
            if (!($playerData instanceof PlayerData)) {
                return null;
            }
            /** @var BooleanAttribute $setting */
            $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_HELPER_ADD);
            if ($setting->getValue() === true) {
                $player->sendMessage($this->getPrefix() . $this->translateString("add.success.player", [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            }
        }
        return null;
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("add.saveError", [$error->getMessage()]));
    }
}