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
class DenySubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.senderNotOnline"]);
            return null;
        }

        if (count($args) === 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.usage"]);
            return null;
        }

        $player = null;
        if ($args[0] !== "*") {
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerName = $player->getName();
            } else {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.playerNotFound" => $playerName]);
                    return null;
                }
                $playerUUID = $playerData->getPlayerUUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.senderIsPlayer"]);
                return null;
            }
        } else {
            $playerUUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.noPlotWorld"]);
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.noPlot"]);
            return null;
        }

        if (!$plot->hasPlotOwner()) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.noPlotOwner"]);
            return null;
        }
        if (!$sender->hasPermission("cplot.admin.deny")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.notPlotOwner"]);
                return null;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.serverPlotFlag" => $flag->getID()]);
            return null;
        }

        if ($plot->isPlotDeniedExact($playerUUID)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.playerAlreadyDenied" => $playerName]);
            return null;
        }

        $playerData = yield DataProvider::getInstance()->awaitPlayerDataByUUID($playerUUID);
        if (!($playerData instanceof PlayerData)) {
            return;
        }
        $plotPlayer = new PlotPlayer($playerData, PlotPlayer::STATE_DENIED);
        $plot->addPlotPlayer($plotPlayer);
        yield DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "deny.success" => $playerName]);

        if ($player instanceof Player) {
            /** @var BooleanAttribute $setting */
            $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_DENIED_ADD);
            if ($setting->getValue() === true) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                    $sender,
                    ["prefix", "deny.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                );
            }
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "deny.saveError" => $error->getMessage()]);
    }
}