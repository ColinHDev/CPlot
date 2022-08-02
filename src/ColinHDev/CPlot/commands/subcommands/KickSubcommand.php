<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlayerKickFromPlotEvent;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function count;

class KickSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.usage"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.kick") && !$plot->isPlotOwner($sender)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.notPlotOwner"]);
            return;
        }

        if ($args[0] === "*") {
            $kickedPlayers = 0;
            foreach($sender->getWorld()->getPlayers() as $target) {
                if ($target === $sender || !$target->isConnected() || !$plot->isOnPlot($target->getPosition())) {
                    continue;
                }
                if ($target->hasPermission("cplot.bypass.kick")) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.hasBypassPermission" => $target->getName()]);
                    continue;
                }
                $event = new PlayerKickFromPlotEvent($plot, $sender, $target);
                $event->call();
                if ($event->isCancelled()) {
                    continue;
                }
                if (!$plot->teleportTo($target, TeleportDestination::ROAD_EDGE)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.couldNotTeleport" => $target->getName()]);
                    continue;
                }
                $kickedPlayers++;
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($target, ["prefix", "kick.targetMessage" => $sender->getName()]);
            }
            if ($kickedPlayers === 0) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.noneToKick"]);
            } else {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.success.playerCount" => $kickedPlayers]);
            }

        } else {
            foreach($args as $arg) {
                $target = $sender->getServer()->getPlayerByPrefix($arg);
                if (!($target instanceof Player)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.playerNotOnline" => $arg]);
                    continue;
                }
                if ($target === $sender) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.senderIsTarget"]);
                    continue;
                }
                if (!$plot->isOnPlot($target->getPosition())) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.playerNotOnPlot" => $target->getName()]);
                    continue;
                }
                if ($target->hasPermission("cplot.bypass.kick")) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.hasBypassPermission" => $target->getName()]);
                    continue;
                }
                $event = new PlayerKickFromPlotEvent($plot, $sender, $target);
                $event->call();
                if ($event->isCancelled()) {
                    continue;
                }
                if (!$plot->teleportTo($target, TeleportDestination::ROAD_EDGE)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.couldNotTeleport" => $target->getName()]);
                    continue;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "kick.success.playerNames" => $target->getName()]);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($target, ["prefix", "kick.targetMessage" => $sender->getName()]);
            }
        }
    }
}