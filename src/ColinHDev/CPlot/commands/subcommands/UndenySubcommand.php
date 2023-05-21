<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\event\PlotPlayerRemoveAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformUndeniedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\lock\RemovePlotPlayerLockID;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class UndenySubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "undeny.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "undeny.usage"]);
            return;
        }

        if ($args[0] !== "*") {
            $player = $sender->getServer()->getPlayerByPrefix($args[0]);
            if ($player instanceof Player) {
                $playerName = $player->getName();
            } else {
                $playerName = $args[0];
                $player = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
            }
        } else {
            $playerName = "*";
            $player = yield from DataProvider::getInstance()->awaitPlayerDataByXUID("*");
        }
        if (!($player instanceof Player) && !($player instanceof PlayerData)) {
            self::sendMessage($sender, ["prefix", "undeny.playerNotFound" => $playerName]);
            return null;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "undeny.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "undeny.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            self::sendMessage($sender, ["prefix", "undeny.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.undeny")) {
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "undeny.notPlotOwner"]);
                return;
            }
        }

        $plotPlayer = $plot->getPlotPlayerExact($player);
        if (!($plotPlayer instanceof PlotPlayer) || $plotPlayer->getState() !== PlotPlayer::STATE_DENIED) {
            self::sendMessage($sender, ["prefix", "undeny.playerNotDenied" => $playerName]);
            return;
        }

        $playerData = $plotPlayer->getPlayerData();
        $lock = new RemovePlotPlayerLockID($playerData->getPlayerID());
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "undeny.plotLocked"]);
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
            self::sendMessage($sender, ["prefix", "undeny.saveError"]);
            $sender->getServer()->getLogger()->logException($exception);
            return;
        } finally {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
        }
        self::sendMessage($sender, ["prefix", "undeny.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_UNDENIED())->equals(InformUndeniedSetting::TRUE())) {
            self::sendMessage(
                $player,
                ["prefix", "undeny.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
    }
}