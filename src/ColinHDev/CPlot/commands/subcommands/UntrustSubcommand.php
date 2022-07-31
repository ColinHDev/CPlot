<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotPlayerRemoveAsyncEvent;
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
use poggit\libasynql\SqlError;

class UntrustSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.usage"]);
            return;
        }

        $player = null;
        if ($args[0] !== "*") {
            $player = Server::getInstance()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerXUID = $player->getXuid();
                $playerName = $player->getName();
            } else {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotFound" => $playerName]);
                    return;
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
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.untrust")) {
            if (!$plot->isPlotOwner($sender)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.notPlotOwner"]);
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.serverPlotFlag" => $flag->getID()]);
            return;
        }

        $playerIdentifier = PlayerData::getIdentifierFromData($playerUUID, $playerXUID, $playerName);
        $plotPlayer = $plot->getPlotPlayerExact($playerIdentifier);
        if (!($plotPlayer instanceof PlotPlayer) || $plotPlayer->getState() !== PlotPlayer::STATE_TRUSTED) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.playerNotTrusted" => $playerName]);
            return;
        }

        /** @phpstan-var PlotPlayerRemoveAsyncEvent $event */
        $event = yield from PlotPlayerRemoveAsyncEvent::create($plot, $plotPlayer, $sender);
        if ($event->isCancelled()) {
            return;
        }

        $playerData = $plotPlayer->getPlayerData();
        $plot->removePlotPlayer($playerIdentifier);
        try {
            yield from DataProvider::getInstance()->deletePlotPlayer($plot, $playerData->getPlayerID());
        } catch (SqlError $exception) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.saveError" => $exception->getMessage()]);
            return;
        }
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "untrust.success" => $playerName]);

        if ($player instanceof Player) {
            /** @var BooleanAttribute $setting */
            $setting = $playerData->getSettingNonNullByID(SettingIDs::SETTING_INFORM_TRUSTED_REMOVE);
            if ($setting->getValue() === true) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                    $sender,
                    ["prefix", "untrust.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                );
            }
        }
    }
}