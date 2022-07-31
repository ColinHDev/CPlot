<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotPlayerAddAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformAddedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\lock\PlotAddHelperLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class AddSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.usage"]);
            return;
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
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
                $playerXUID = $playerData->getPlayerXUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.senderIsPlayer"]);
                return;
            }
        } else {
            $playerUUID = "*";
            $playerXUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.add")) {
            if (!$plot->isPlotOwner($sender)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.notPlotOwner"]);
                return;
            }
        }

        if (!($playerData instanceof PlayerData)) {
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByData($playerUUID, $playerXUID, $playerName);
            if (!($playerData instanceof PlayerData)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerNotFound" => $playerName]);
                return;
            }
        }
        if ($plot->isPlotHelperExact($playerData)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.playerAlreadyHelper" => $playerName]);
            return;
        }

        $plotLockID = new PlotAddHelperLockID();
        if (!PlotLockManager::getInstance()->isPlotLocked($plot) && PlotLockManager::getInstance()->lockPlotSilent($plot, $plotLockID)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.plotLocked"]);
            return;
        }

        $plotPlayer = new PlotPlayer($playerData, PlotPlayer::STATE_HELPER);
        /** @phpstan-var PlotPlayerAddAsyncEvent $event */
        $event = yield from PlotPlayerAddAsyncEvent::create($plot, $plotPlayer, $sender);
        if ($event->isCancelled()) {
            return;
        }

        $plot->addPlotPlayer($plotPlayer);
        try {
            yield from DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        } catch (SqlError $exception) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.saveError" => $exception->getMessage()]);
            return;
        }
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "add.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_ADDED())->equals(InformAddedSetting::TRUE())) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                $player,
                ["prefix", "add.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
    }
}