<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class VisitSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.senderNotOnline"]);
            return null;
        }

        switch (count($args)) {
            case 0:
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    return null;
                }
                /** @var Plot[] $plots */
                $plots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                if (count($plots) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.noPlots"]);
                    return null;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[0];
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                    return null;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                return null;

            case 1:
                if (is_numeric($args[0])) {
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                    if (!($playerData instanceof PlayerData)) {
                        return null;
                    }
                    /** @var Plot[] $plots */
                    $plots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                    if (count($plots) === 0) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.noPlots"]);
                        return null;
                    }
                    $plotNumber = (int) $args[0];
                    if ($plotNumber > count($plots)) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.noPlot" => $plotNumber]);
                        return null;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[($plotNumber - 1)];
                    if (!($plot->teleportTo($sender))) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.teleportError" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                        return null;
                    }
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.success" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                    return null;
                }

                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if ($player instanceof Player) {
                    /** @phpstan-var PlayerData|null $playerData */
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                    $playerName = $player->getName();
                } else {
                    $playerName = $args[0];
                    /** @phpstan-var PlayerData|null $playerData */
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);

                }

                if ($playerData instanceof PlayerData) {
                    /** @var Plot[] $plots */
                    $plots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                    if (count($plots) === 0) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.noPlots" => $playerName]);
                        return null;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[0];
                    if (!($plot->teleportTo($sender))) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                        return null;
                    }
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                    return null;
                }

                $alias = strtolower($args[0]);
                /** @var Plot|null $plot */
                $plot = yield DataProvider::getInstance()->awaitPlotByAlias($alias);
                if (!($plot instanceof Plot)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.noPlot" => $alias]);
                    return null;
                }
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
                    return null;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
                return null;

            default:
                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if ($player instanceof Player) {
                    /** @phpstan-var PlayerData|null $playerData */
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                    $playerName = $player->getName();
                } else {
                    $playerName = $args[0];
                    /** @phpstan-var PlayerData|null $playerData */
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                }
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.playerNotFound" => $playerName]);
                    return null;
                }

                /** @var Plot[] $plots */
                $plots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                if (count($plots) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.noPlots" => $playerName]);
                    return null;
                }
                $plotNumber = (int) $args[1];
                if ($plotNumber > count($plots)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.noPlot" => [$playerName, $plotNumber]]);
                    return null;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[($plotNumber - 1)];
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.teleportError" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                    return null;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.success" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "visit.loadPlotsError" => $error->getMessage()]);
    }
}