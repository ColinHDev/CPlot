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
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<null>
 */
class UntrustSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.senderNotOnline"]);
            return null;
        }

        if (count($args) === 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.usage"]);
            return null;
        }

        $player = null;
        if ($args[0] !== "*") {
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerXUID = $player->getXuid();
                $playerName = $player->getName();
            } else {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotFound" => $playerName]);
                    return null;
                }
                $playerUUID = $playerData->getPlayerUUID();
                $playerXUID = $playerData->getPlayerXUID();
            }
        } else {
            $playerUUID = "*";
            $playerXUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlotWorld"]);
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlot"]);
            return null;
        }

        if (!$plot->hasPlotOwner()) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlotOwner"]);
            return null;
        }
        if (!$sender->hasPermission("cplot.admin.untrust")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.notPlotOwner"]);
                return null;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.serverPlotFlag" => $flag->getID()]);
            return null;
        }

        $playerIdentifier = PlayerData::getIdentifierFromData($playerUUID, $playerXUID, $playerName);
        $plotPlayer = $plot->getPlotPlayerExact($playerIdentifier);
        if (!($plotPlayer instanceof PlotPlayer) || $plotPlayer->getState() !== PlotPlayer::STATE_TRUSTED) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotTrusted" => $playerName]);
            return null;
        }
        $playerData = $plotPlayer->getPlayerData();

        $plot->removePlotPlayer($playerIdentifier);
        yield DataProvider::getInstance()->deletePlotPlayer($plot, $playerData->getPlayerID());
        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.success" => $playerName]);

        if ($player instanceof Player) {
            /** @var BooleanAttribute $setting */
            $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_TRUSTED_REMOVE);
            if ($setting->getValue() === true) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                    $sender,
                    ["prefix", "untrust.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                );
            }
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "untrust.saveError" => $error->getMessage()]);
    }
}