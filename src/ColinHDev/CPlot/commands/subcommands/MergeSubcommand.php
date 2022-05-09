<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotMergeAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

/**
 * @phpstan-extends Subcommand<null>
 */
class MergeSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.senderNotOnline"]);
            return null;
        }

        $location = $sender->getLocation();
        assert($location->world instanceof World);
        $worldName = $location->world->getFolderName();
        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.noPlotWorld"]);
            return null;
        }

        $basePlot = BasePlot::fromVector3($worldName, $worldSettings, $location);
        $plot = null;
        if ($basePlot instanceof BasePlot) {
            /** @var Plot|null $plot */
            $plot = yield $basePlot->toAsyncPlot();
        }
        if ($basePlot === null || $plot === null) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.noPlot"]);
            return null;
        }

        if (!$plot->hasPlotOwner()) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.noPlotOwner"]);
            return null;
        }
        if (!$sender->hasPermission("cplot.admin.merge")) {
            if (!$plot->isPlotOwner($sender)) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.notPlotOwner"]);
                return null;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.serverPlotFlag" => $flag->getID()]);
            return null;
        }

        $rotation = ($location->yaw - 180) % 360;
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
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.processDirectionError"]);
            return null;
        }

        /** @var BasePlot $basePlotToMerge */
        $basePlotToMerge = $basePlot->getSide($direction);
        $plotToMerge = yield $basePlotToMerge->toAsyncPlot();
        if (!($plotToMerge instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.invalidSecondPlot"]);
            return null;
        }
        if ($plot->isSame($plotToMerge)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.alreadyMerged"]);
            return null;
        }

        $hasSameOwner = false;
        foreach ($plotToMerge->getPlotOwners() as $plotOwner) {
            if ($plot->isPlotOwner($plotOwner->getPlayerData())) {
                $hasSameOwner = true;
                break;
            }
        }
        if (!$hasSameOwner) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.notSamePlotOwner"]);
            return null;
        }

        /** @var BooleanAttribute $flag */
        $flag = $plotToMerge->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.secondPlotServerPlotFlag" => $flag->getID()]);
            return null;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getMergePrice();
            if ($price > 0.0) {
                yield $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getMergeReason());
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "merge.start"]);
        $world = $sender->getWorld();
        $task = new PlotMergeAsyncTask($world, $worldSettings, $plot, $plotToMerge);
        $task->setCallback(
            static function (int $elapsedTime, string $elapsedTimeString, mixed $result) use ($world, $plot, $sender) : void {
                $plotCount = count($plot->getMergePlots()) + 1;
                $plots = array_map(
                    static function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    array_merge([$plot], $plot->getMergePlots())
                );
                Server::getInstance()->getLogger()->debug(
                    "Merging plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "merge.finish" => $elapsedTimeString]);
            }
        );
        yield DataProvider::getInstance()->awaitPlotDeletion($plotToMerge);
        yield $plot->merge($plotToMerge);
        Server::getInstance()->getAsyncPool()->submitTask($task);
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        if ($error instanceof EconomyException) {
            LanguageManager::getInstance()->getProvider()->translateForCommandSender(
                $sender,
                $error->getLanguageKey(),
                static function(string $errorMessage) use($sender) : void {
                    $economyManager = EconomyManager::getInstance();
                    $economyProvider = $economyManager->getProvider();
                    // This exception should not be thrown if no economy provider is set.
                    assert($economyProvider instanceof EconomyProvider);
                    LanguageManager::getInstance()->getProvider()->sendMessage(
                        $sender,
                        [
                            "prefix",
                            "claim.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($economyManager->getMergePrice()),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                }
            );
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "merge.deleteError" => $error->getMessage()]);
    }
}