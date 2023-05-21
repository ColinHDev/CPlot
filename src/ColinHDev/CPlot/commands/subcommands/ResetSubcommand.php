<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\lock\ResetLockID;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\tasks\async\PlotResetAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

class ResetSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "reset.senderNotOnline"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "reset.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "reset.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.reset")) {
            if (!$plot->hasPlotOwner()) {
                self::sendMessage($sender, ["prefix", "reset.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "reset.notPlotOwner"]);
                return;
            }
        }

        $lock = new ResetLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "reset.plotLocked"]);
            return;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getResetPrice();
            if ($price > 0.0) {
                try {
                    yield from $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getResetReason());
                } catch(EconomyException $exception) {
                    $errorMessage = self::translateForCommandSender($sender, $exception->getLanguageKey());
                    self::sendMessage(
                        $sender, [
                            "prefix",
                            "reset.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($price),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                    PlotLockManager::getInstance()->unlockPlots($lock, $plot);
                    return;
                }
                self::sendMessage($sender, ["prefix", "reset.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        self::sendMessage($sender, ["prefix", "reset.start"]);
        /** @phpstan-var PlotResetAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->reset($resolve)
        );
        $world = $sender->getWorld();
        $plotCount = count($plot->getMergePlots()) + 1;
        $plots = array_map(
            static function (BasePlot $plot) : string {
                return $plot->toSmallString();
            },
            array_merge([$plot], $plot->getMergePlots())
        );
        $elapsedTimeString = $task->getElapsedTimeString();
        Server::getInstance()->getLogger()->debug(
            "Resetting plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($sender, ["prefix", "reset.finish" => $elapsedTimeString]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }
}