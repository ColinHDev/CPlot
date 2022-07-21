<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotPlayerAddAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformAddedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\ServerPlotFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class AddSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.senderNotOnline"]);
            return null;
        }

        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.usage"]);
            return null;
        }

        $player = null;
        $playerData = null;
        if ($args[0] !== "*") {
            $player = $sender->getServer()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerUUID = $player->getUniqueId()->getBytes();
                $playerXUID = $player->getXuid();
                $playerName = $player->getName();
            } else {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerNotFound" => $playerName]);
                    return null;
                }
                $playerUUID = $playerData->getPlayerUUID();
                $playerXUID = $playerData->getPlayerXUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.senderIsPlayer"]);
                return null;
            }
        } else {
            $playerUUID = "*";
            $playerXUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlotWorld"]);
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlot"]);
            return null;
        }

        if (!$plot->hasPlotOwner()) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlotOwner"]);
            return null;
        }
        if (!$sender->hasPermission("cplot.admin.add")) {
            if (!$plot->isPlotOwner($sender)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.notPlotOwner"]);
                return null;
            }
        }

        $flag = $plot->getFlag(Flags::SERVER_PLOT());
        if ($flag->equals(ServerPlotFlag::TRUE())) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.serverPlotFlag" => $flag->getID()]);
            return null;
        }

        if (!($playerData instanceof PlayerData)) {
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByData($playerUUID, $playerXUID, $playerName);
            if (!($playerData instanceof PlayerData)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerNotFound" => $playerName]);
                return null;
            }
        }
        if ($plot->isPlotHelperExact($playerData)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerAlreadyHelper" => $playerName]);
            return null;
        }

        $plotPlayer = new PlotPlayer($playerData, PlotPlayer::STATE_HELPER);
        /** @phpstan-var PlotPlayerAddAsyncEvent $event */
        $event = yield from PlotPlayerAddAsyncEvent::create($plot, $plotPlayer, $sender);
        if ($event->isCancelled()) {
            return null;
        }

        $plot->addPlotPlayer($plotPlayer);
        yield DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_ADDED())->equals(InformAddedSetting::TRUE())) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                $player,
                ["prefix", "add.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "add.saveError" => $error->getMessage()]);
    }
}