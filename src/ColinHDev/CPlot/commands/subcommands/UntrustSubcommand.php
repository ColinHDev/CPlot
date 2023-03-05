<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\event\PlotPlayerRemoveAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformUntrustedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\lock\RemovePlotPlayerLockID;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class UntrustSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "untrust.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "untrust.usage"]);
            return;
        }

        if ($args[0] !== "*") {
            $player = $sender->getServer()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerName = $player->getName();
            } else {
                self::sendMessage($sender, ["prefix", "untrust.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $player = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
            }
        } else {
            $playerName = "*";
            $player = yield from DataProvider::getInstance()->awaitPlayerDataByXUID("*");
        }
        if (!($player instanceof Player) && !($player instanceof PlayerData)) {
            self::sendMessage($sender, ["prefix", "untrust.playerNotFound" => $playerName]);
            return null;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "untrust.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "untrust.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            self::sendMessage($sender, ["prefix", "untrust.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.untrust")) {
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "untrust.notPlotOwner"]);
                return;
            }
        }

        $plotPlayer = $plot->getPlotPlayerExact($player);
        if (!($plotPlayer instanceof PlotPlayer) || $plotPlayer->getState() !== PlotPlayer::STATE_TRUSTED) {
            self::sendMessage($sender, ["prefix", "untrust.playerNotTrusted" => $playerName]);
            return;
        }

        $playerData = $plotPlayer->getPlayerData();
        $lock = new RemovePlotPlayerLockID($playerData->getPlayerID());
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "untrust.plotLocked"]);
            return;
        }

        /** @phpstan-var PlotPlayerRemoveAsyncEvent $event */
        $event = yield from PlotPlayerRemoveAsyncEvent::create($plot, $plotPlayer, $sender);
        if ($event->isCancelled()) {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
            return;
        }

        $plot->removePlotPlayer($plotPlayer);
        try {
            yield from DataProvider::getInstance()->deletePlotPlayer($plot, $playerData->getPlayerID());
        } catch (SqlError $exception) {
            self::sendMessage($sender, ["prefix", "untrust.saveError" => $exception->getMessage()]);
            return;
        } finally {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
        }
        self::sendMessage($sender, ["prefix", "untrust.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_UNTRUSTED())->equals(InformUntrustedSetting::TRUE())) {
            self::sendMessage(
                $player,
                ["prefix", "untrust.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
    }
}