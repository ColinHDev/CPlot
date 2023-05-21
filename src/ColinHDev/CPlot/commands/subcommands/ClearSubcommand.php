<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\ClearLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\tasks\async\PlotClearAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

class ClearSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "clear.senderNotOnline"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "clear.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "clear.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.clear")) {
            if (!$plot->hasPlotOwner()) {
                self::sendMessage($sender, ["prefix", "clear.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "clear.notPlotOwner"]);
                return;
            }
        }

        $lock = new ClearLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "clear.plotLocked"]);
            return;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getClearPrice();
            if ($price > 0.0) {
                try {
                    yield from $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getClearReason());
                } catch(EconomyException $exception) {
                    $errorMessage = self::translateForCommandSender($sender, $exception->getLanguageKey());
                    self::sendMessage(
                        $sender, [
                            "prefix",
                            "clear.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($price),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                    PlotLockManager::getInstance()->unlockPlots($lock, $plot);
                    return;
                }
                self::sendMessage($sender, ["prefix", "clear.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        self::sendMessage($sender, ["prefix", "clear.start"]);
        /** @phpstan-var PlotClearAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->clear($resolve)
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
            "Clearing plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($sender, ["prefix", "clear.finish" => $elapsedTimeString]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }
}