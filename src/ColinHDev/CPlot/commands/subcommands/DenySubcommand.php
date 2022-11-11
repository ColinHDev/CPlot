<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotPlayerAddAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformDeniedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\lock\AddPlotPlayerLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class DenySubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "deny.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "deny.usage"]);
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
                self::sendMessage($sender, ["prefix", "deny.playerNotOnline" => $args[0]]);
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "deny.playerNotFound" => $playerName]);
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
                $playerXUID = $playerData->getPlayerXUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                self::sendMessage($sender, ["prefix", "deny.senderIsPlayer"]);
                return;
            }
        } else {
            $playerUUID = "*";
            $playerXUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "deny.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "deny.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            self::sendMessage($sender, ["prefix", "deny.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.deny")) {
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "deny.notPlotOwner"]);
                return;
            }
        }

        if (!($playerData instanceof PlayerData)) {
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByData($playerUUID, $playerXUID, $playerName);
            if (!($playerData instanceof PlayerData)) {
                self::sendMessage($sender, ["prefix", "deny.playerNotFound" => $playerName]);
                return;
            }
        }
        if ($plot->isPlotDeniedExact($playerData)) {
            self::sendMessage($sender, ["prefix", "deny.playerAlreadyDenied" => $playerName]);
            return;
        }

        $lock = new AddPlotPlayerLockID($playerData->getPlayerID());
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "deny.plotLocked"]);
            return;
        }

        $plotPlayer = new PlotPlayer($playerData, PlotPlayer::STATE_DENIED);
        /** @phpstan-var PlotPlayerAddAsyncEvent $event */
        $event = yield from PlotPlayerAddAsyncEvent::create($plot, $plotPlayer, $sender);
        if ($event->isCancelled()) {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
            return;
        }

        $plot->addPlotPlayer($plotPlayer);
        try {
            yield from DataProvider::getInstance()->savePlotPlayer($plot, $plotPlayer);
        } catch (SqlError $exception) {
            self::sendMessage($sender, ["prefix", "deny.saveError" => $exception->getMessage()]);
            return;
        } finally {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
        }
        self::sendMessage($sender, ["prefix", "deny.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_DENIED())->equals(InformDeniedSetting::TRUE())) {
            self::sendMessage(
                $player,
                ["prefix", "deny.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
    }
}