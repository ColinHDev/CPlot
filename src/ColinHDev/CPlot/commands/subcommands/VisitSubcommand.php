<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use function array_values;
use function assert;
use function count;
use function is_numeric;
use function strtolower;

class VisitSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "visit.senderNotOnline"]);
            return;
        }

        switch (count($args)) {
            case 0:
                yield from $this->teleportByPlotNumber($sender, 1);
                break;
            case 1:
                if (is_numeric($args[0])) {
                    yield from $this->teleportByPlotNumber($sender, (int) $args[0]);
                    break;
                }
                /** @var PlayerData|null $playerData */
                $playerData = yield from $this->getPlayerDataByName($args[0]);
                if ($playerData instanceof PlayerData) {
                    yield from $this->teleportByPlayer($sender, $playerData, 1);
                    break;
                }
                yield from $this->teleportByPlotAlias($sender, strtolower($args[0]));
                break;
            case 2:
                /** @var PlayerData|null $playerData */
                $playerData = yield from $this->getPlayerDataByName($args[0]);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "visit.other.playerNotFound" => $args[0]]);
                    return;
                }
                if (is_numeric($args[1])) {
                    $plotNumber = (int) $args[1];
                } else {
                    $plotNumber = 1;
                }
                yield from $this->teleportByPlayer($sender, $playerData, $plotNumber);
                break;
            default:
                self::sendMessage($sender, ["prefix", "visit.usage"]);
                break;
        }
    }

    private function getPlayerDataByName(string $playerName) : Generator {
        $player = Server::getInstance()->getPlayerByPrefix($playerName);
        if ($player instanceof Player) {
            /** @phpstan-var PlayerData|null $playerData */
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
        } else {
            /** @phpstan-var PlayerData|null $playerData */
            $playerData = yield DataProvider::getInstance()->awaitPlayerDataByName($playerName);
        }
        return $playerData;
    }

    private function teleportByPlotNumber(Player $sender, int $plotNumber) : Generator {
        $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
        assert($playerData instanceof PlayerData);
        try {
            /** @var Plot[] $plots */
            $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
        } catch(SqlError $exception) {
            self::sendMessage($sender, ["prefix", "visit.loadPlotsError"]);
            $sender->getServer()->getLogger()->logException($exception);
            return;
        }
        if (count($plots) === 0) {
            self::sendMessage($sender, ["prefix", "visit.self.noPlots"]);
            return;
        }
        if ($plotNumber > count($plots)) {
            self::sendMessage($sender, ["prefix", "visit.self.noPlot" => $plotNumber]);
            return;
        }
        /** @var Plot $plot */
        $plot = array_values($plots)[($plotNumber - 1)];
        if (!($plot->teleportTo($sender))) {
            self::sendMessage($sender, ["prefix", "visit.self.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $plotNumber]]);
            return;
        }
        self::sendMessage($sender, ["prefix", "visit.self.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $plotNumber]]);
    }

    private function teleportByPlayer(Player $sender, PlayerData $player, int $plotNumber) : Generator {
        try {
            /** @var Plot[] $plots */
            $plots = yield from DataProvider::getInstance()->awaitPlotsByPlotPlayer($player->getPlayerID(), PlotPlayer::STATE_OWNER);
        } catch(SqlError $exception) {
            self::sendMessage($sender, ["prefix", "visit.loadPlotsError" => $exception->getMessage()]);
            return;
        }
        if (count($plots) === 0) {
            self::sendMessage($sender, ["prefix", "visit.other.noPlots" => $player->getPlayerName() ?? "Unknown"]);
            return;
        }
        if ($plotNumber > count($plots)) {
            self::sendMessage($sender, ["prefix", "visit.other.noPlot" => [$player->getPlayerName() ?? "Unknown", $plotNumber]]);
            return;
        }
        /** @var Plot $plot */
        $plot = array_values($plots)[($plotNumber - 1)];
        if (!($plot->teleportTo($sender))) {
            self::sendMessage($sender, ["prefix", "visit.oneArgument.player.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $player->getPlayerName() ?? "Unknown", $plotNumber]]);
            return;
        }
        self::sendMessage($sender, ["prefix", "visit.oneArgument.player.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $player->getPlayerName() ?? "Unknown", $plotNumber]]);
    }
    private function teleportByPlotAlias(Player $sender, string $alias) : Generator {
        try {
            /** @var Plot|null $plot */
            $plot = yield from DataProvider::getInstance()->awaitPlotByAlias($alias);
        } catch(SqlError $exception) {
            self::sendMessage($sender, ["prefix", "visit.loadPlotsError"]);
            $sender->getServer()->getLogger()->logException($exception);
            return;
        }
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "visit.alias.noPlot" => $alias]);
            return;
        }
        if (!($plot->teleportTo($sender))) {
            self::sendMessage($sender, ["prefix", "visit.alias.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
            return;
        }
        self::sendMessage($sender, ["prefix", "visit.alias.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ(), $alias]]);
    }
}
