<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\event\PlayerKickFromPlotEvent;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

class KickSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "kick.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "kick.usage"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "kick.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "kick.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.kick") && !$plot->isPlotOwner($sender)) {
            self::sendMessage($sender, ["prefix", "kick.notPlotOwner"]);
            return;
        }

        if ($args[0] === "*") {
            $kickedPlayers = 0;
            foreach($sender->getWorld()->getPlayers() as $target) {
                if ($target === $sender || !$target->isConnected() || !$plot->isOnPlot($target->getPosition())) {
                    continue;
                }
                if ($target->hasPermission("cplot.bypass.kick")) {
                    self::sendMessage($sender, ["prefix", "kick.hasBypassPermission" => $target->getName()]);
                    continue;
                }
                $event = new PlayerKickFromPlotEvent($plot, $sender, $target);
                $event->call();
                if ($event->isCancelled()) {
                    continue;
                }
                if (!$plot->teleportTo($target, TeleportDestination::ROAD_EDGE)) {
                    self::sendMessage($sender, ["prefix", "kick.couldNotTeleport" => $target->getName()]);
                    continue;
                }
                $kickedPlayers++;
                self::sendMessage($target, ["prefix", "kick.targetMessage" => $sender->getName()]);
            }
            if ($kickedPlayers === 0) {
                self::sendMessage($sender, ["prefix", "kick.noneToKick"]);
            } else {
                self::sendMessage($sender, ["prefix", "kick.success.playerCount" => $kickedPlayers]);
            }

        } else {
            foreach($args as $arg) {
                $target = $sender->getServer()->getPlayerByPrefix($arg);
                if (!($target instanceof Player)) {
                    self::sendMessage($sender, ["prefix", "kick.playerNotOnline" => $arg]);
                    continue;
                }
                if ($target === $sender) {
                    self::sendMessage($sender, ["prefix", "kick.senderIsTarget"]);
                    continue;
                }
                if (!$plot->isOnPlot($target->getPosition())) {
                    self::sendMessage($sender, ["prefix", "kick.playerNotOnPlot" => $target->getName()]);
                    continue;
                }
                if ($target->hasPermission("cplot.bypass.kick")) {
                    self::sendMessage($sender, ["prefix", "kick.hasBypassPermission" => $target->getName()]);
                    continue;
                }
                $event = new PlayerKickFromPlotEvent($plot, $sender, $target);
                $event->call();
                if ($event->isCancelled()) {
                    continue;
                }
                if (!$plot->teleportTo($target, TeleportDestination::ROAD_EDGE)) {
                    self::sendMessage($sender, ["prefix", "kick.couldNotTeleport" => $target->getName()]);
                    continue;
                }
                self::sendMessage($sender, ["prefix", "kick.success.playerNames" => $target->getName()]);
                self::sendMessage($target, ["prefix", "kick.targetMessage" => $sender->getName()]);
            }
        }
    }
}