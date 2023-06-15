<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\MergeLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\tasks\async\PlotMergeAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use SOFe\AwaitGenerator\Await;

class MergeSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "merge.senderNotOnline"]);
            return;
        }

        $location = $sender->getLocation();
        assert($location->world instanceof World);
        $worldName = $location->world->getFolderName();
        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "merge.noPlotWorld"]);
            return;
        }

        $basePlot = BasePlot::fromVector3($worldName, $worldSettings, $location);
        $plot = null;
        if ($basePlot instanceof BasePlot) {
            /** @var Plot|null $plot */
            $plot = yield $basePlot->toAsyncPlot();
        }
        if ($basePlot === null || $plot === null) {
            self::sendMessage($sender, ["prefix", "merge.noPlot"]);
            return;
        }

        if (!$plot->hasPlotOwner()) {
            self::sendMessage($sender, ["prefix", "merge.noPlotOwner"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.merge")) {
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "merge.notPlotOwner"]);
                return;
            }
        }

        $rotation = (((int) $location->yaw) - 180) % 360;
        if ($rotation < 0) $rotation += 360.0;

        if ((0 <= $rotation && $rotation < 45) || (315 <= $rotation && $rotation < 360)) {
            $direction = Facing::NORTH;
        } else if (45 <= $rotation && $rotation < 135) {
            $direction = Facing::EAST;
        } else if (135 <= $rotation && $rotation < 225) {
            $direction = Facing::SOUTH;
        } else if (225 <= $rotation && $rotation < 315) {
            $direction = Facing::WEST;
        } else {
            self::sendMessage($sender, ["prefix", "merge.processDirectionError"]);
            return;
        }

        /** @var BasePlot $basePlotToMerge */
        $basePlotToMerge = $basePlot->getSide($direction);
        $plotToMerge = yield $basePlotToMerge->toAsyncPlot();
        if (!($plotToMerge instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "merge.invalidSecondPlot"]);
            return;
        }
        if ($plot->isSame($plotToMerge)) {
            self::sendMessage($sender, ["prefix", "merge.alreadyMerged"]);
            return;
        }

        $hasSameOwner = false;
        foreach ($plotToMerge->getPlotOwners() as $plotOwner) {
            if ($plot->isPlotOwner($plotOwner->getPlayerData())) {
                $hasSameOwner = true;
                break;
            }
        }
        if (!$hasSameOwner) {
            self::sendMessage($sender, ["prefix", "merge.notSamePlotOwner"]);
            return;
        }

        $lock = new MergeLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot, $plotToMerge)) {
            PlotLockManager::getInstance()->unlockPlots($lock, $plot, $plotToMerge);
            self::sendMessage($sender, ["prefix", "merge.plotLocked"]);
            return;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getMergePrice();
            if ($price > 0.0) {
                try {
                    yield from $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getMergeReason());
                } catch(EconomyException $exception) {
                    $errorMessage = self::translateForCommandSender($sender, $exception->getLanguageKey());
                    self::sendMessage(
                        $sender, [
                            "prefix",
                            "merge.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($price),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                    PlotLockManager::getInstance()->unlockPlots($lock, $plot);
                    PlotLockManager::getInstance()->unlockPlots($lock, $plotToMerge);
                    return;
                }
                self::sendMessage($sender, ["prefix", "merge.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        self::sendMessage($sender, ["prefix", "merge.start"]);
        /** @phpstan-var PlotMergeAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->merge($plotToMerge, $resolve)
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
            "Merging plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($sender, ["prefix", "merge.finish" => $elapsedTimeString]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }
}