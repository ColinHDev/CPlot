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
use poggit\libasynql\SqlError;

class VisitSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.senderNotOnline"]);
            return;
        }

        switch (count($args)) {
            case 0:
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    return;
                }
                try {
                    /** @var Plot[] $plots */
                    $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                } catch(SqlError $exception) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
                    return;
                }
                if (count($plots) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.noPlots"]);
                    return;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[0];
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                    return;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.noArguments.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                return;

            case 1:
                if (is_numeric($args[0])) {
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                    if (!($playerData instanceof PlayerData)) {
                        return;
                    }
                    try {
                        /** @var Plot[] $plots */
                        $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                    } catch(SqlError $exception) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
                        return;
                    }
                    if (count($plots) === 0) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.noPlots"]);
                        return;
                    }
                    $plotNumber = (int) $args[0];
                    if ($plotNumber > count($plots)) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.noPlot" => $plotNumber]);
                        return;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[($plotNumber - 1)];
                    if (!($plot->teleportTo($sender))) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.teleportError" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                        return;
                    }
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.sender.success" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
                    return;
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
                    try {
                        /** @var Plot[] $plots */
                        $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                    } catch(SqlError $exception) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
                        return;
                    }
                    if (count($plots) === 0) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.noPlots" => $playerName]);
                        return;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[0];
                    if (!($plot->teleportTo($sender))) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                        return;
                    }
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.player.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                    return;
                }

                $alias = strtolower($args[0]);
                try {
                    /** @var Plot|null $plot */
                    $plot = yield from DataProvider::getInstance()->awaitPlotByAlias($alias);
                } catch(SqlError $exception) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
                    return;
                }
                if (!($plot instanceof Plot)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.noPlot" => $alias]);
                    return;
                }
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
                    return;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.oneArgument.alias.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
                return;

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
                    return;
                }

                try {
                    /** @var Plot[] $plots */
                    $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
                } catch(SqlError $exception) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
                    return;
                }
                if (count($plots) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.noPlots" => $playerName]);
                    return;
                }
                $plotNumber = (int) $args[1];
                if ($plotNumber > count($plots)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.noPlot" => [$playerName, $plotNumber]]);
                    return;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[($plotNumber - 1)];
                if (!($plot->teleportTo($sender))) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.teleportError" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
                    return;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "visit.twoArguments.success" => [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]]);
        }
    }
}