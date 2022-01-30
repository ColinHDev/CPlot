<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<null>
 */
class VisitSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("visit.senderNotOnline"));
            return null;
        }

        switch (count($args)) {
            case 0:
                /** @var Plot[] $plots */
                $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($sender->getUniqueId()->getBytes(), PlotPlayer::STATE_OWNER);
                if (count($plots) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.noPlots"));
                    return null;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[0];
                if (!($plot->teleportTo($sender))) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                    return null;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("visit.noArguments.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                return null;

            case 1:
                if (is_numeric($args[0])) {
                    /** @var Plot[] $plots */
                    $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($sender->getUniqueId()->getBytes(), PlotPlayer::STATE_OWNER);
                    if (count($plots) === 0) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.noPlots"));
                        return null;
                    }
                    $plotNumber = (int) $args[0];
                    if ($plotNumber > count($plots)) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.noPlot", [$plotNumber]));
                        return null;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[($plotNumber - 1)];
                    if (!($plot->teleportTo($sender))) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.teleportError", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                        return null;
                    }
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.sender.success", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                    return null;
                }

                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if ($player instanceof Player) {
                    $playerUUID = $player->getUniqueId()->getBytes();
                    $playerName = $player->getName();
                } else {
                    $playerName = $args[0];
                    $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                    $playerUUID = $playerData?->getPlayerUUID();
                }

                if ($playerUUID !== null) {
                    /** @var Plot[] $plots */
                    $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerUUID, PlotPlayer::STATE_OWNER);
                    if (count($plots) === 0) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.noPlots", [$playerName]));
                        return null;
                    }
                    /** @var Plot $plot */
                    $plot = array_values($plots)[0];
                    if (!($plot->teleportTo($sender))) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                        return null;
                    }
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.player.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                    return null;
                }

                $alias = strtolower($args[0]);
                /** @var Plot|null $plot */
                $plot = yield from DataProvider::getInstance()->awaitPlotByAlias($alias);
                if (!($plot instanceof Plot)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.noPlot", [$alias]));
                    return null;
                }
                if (!($plot->teleportTo($sender))) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]));
                    return null;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("visit.oneArgument.alias.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]));
                return null;

            default:
                $player = Server::getInstance()->getPlayerByPrefix($args[0]);
                if ($player instanceof Player) {
                    $playerUUID = $player->getUniqueId()->getBytes();
                    $playerName = $player->getName();
                } else {
                    $playerName = $args[0];
                    $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByName($playerName);
                    if ($playerData === null) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.playerNotFound", [$playerName]));
                        return null;
                    }
                    $playerUUID = $playerData->getPlayerUUID();
                }

                /** @var Plot[] $plots */
                $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerUUID, PlotPlayer::STATE_OWNER);
                if (count($plots) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.noPlots", [$playerName]));
                    return null;
                }
                $plotNumber = (int) $args[1];
                if ($plotNumber > count($plots)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.noPlot", [$playerName, $plotNumber]));
                    return null;
                }
                /** @var Plot $plot */
                $plot = array_values($plots)[($plotNumber - 1)];
                if (!($plot->teleportTo($sender))) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.teleportError", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
                    return null;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("visit.twoArguments.success", [$plotNumber, $plot->getWorldName(), $plot->getX(), $plot->getZ(), $playerName]));
        }
        return null;
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("visit.loadPlotsError", [$error->getMessage()]));
    }
}