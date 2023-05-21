<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\event\PlotPlayerAddAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\implementation\InformTrustedSetting;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\lock\AddPlotPlayerLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class TrustSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "trust.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "trust.usage"]);
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
                $playerName = $args[0];
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "trust.playerNotFound" => $playerName]);
                    return;
                }
                $playerUUID = $playerData->getPlayerUUID();
                $playerXUID = $playerData->getPlayerXUID();
            }
            if ($playerUUID === $sender->getUniqueId()->getBytes()) {
                self::sendMessage($sender, ["prefix", "trust.senderIsPlayer"]);
                return;
            }
        } else {
            $playerUUID = "*";
            $playerXUID = "*";
            $playerName = "*";
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "trust.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "trust.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            self::sendMessage($sender, ["prefix", "trust.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.trust")) {
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "trust.notPlotOwner"]);
                return;
            }
        }

        if (!($playerData instanceof PlayerData)) {
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByData($playerUUID, $playerXUID, $playerName);
            if (!($playerData instanceof PlayerData)) {
                self::sendMessage($sender, ["prefix", "trust.playerNotFound" => $playerName]);
                return;
            }
        }
        if ($plot->isPlotTrustedExact($playerData)) {
            self::sendMessage($sender, ["prefix", "trust.playerAlreadyTrusted" => $playerName]);
            return;
        }

        $lock = new AddPlotPlayerLockID($playerData->getPlayerID());
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "trust.plotLocked"]);
            return;
        }

        $plotPlayer = new PlotPlayer($playerData, PlotPlayer::STATE_TRUSTED);
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
            self::sendMessage($sender, ["prefix", "trust.saveError"]);
            $sender->getServer()->getLogger()->logException($exception);
            return;
        } finally {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot);
        }
        self::sendMessage($sender, ["prefix", "trust.success" => $playerName]);

        if ($player instanceof Player && $playerData->getSetting(Settings::INFORM_TRUSTED())->equals(InformTrustedSetting::TRUE())) {
            self::sendMessage(
                $player,
                ["prefix", "trust.success.player" => [$sender->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
        }
    }
}