<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlot\tasks\async\PlotMergeAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<null>
 */
class MergeSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.senderNotOnline"));
            return;
        }

        $location = $sender->getLocation();
        $worldName = $location->world->getFolderName();
        $worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlotWorld"));
            return;
        }

        $basePlot = BasePlot::fromVector3($worldName, $worldSettings, $location);
        $plot = null;
        if ($basePlot instanceof BasePlot) {
            /** @var Plot|null $plot */
            $plot = yield from $basePlot->toAsyncPlot();
        }
        if ($basePlot === null || $plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlot"));
            return;
        }

        if (!$plot->hasPlotOwner()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.merge")) {
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notPlotOwner"));
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.serverPlotFlag", [$flag->getID()]));
            return;
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
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.processDirectionError"));
            return;
        }

        /** @var BasePlot $basePlotToMerge */
        $basePlotToMerge = $basePlot->getSide($direction);
        $plotToMerge = yield from $basePlotToMerge->toAsyncPlot();
        if ($plotToMerge === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.invalidSecondPlot"));
            return;
        }
        if ($plot->isSame($plotToMerge)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.alreadyMerged"));
            return;
        }

        $hasSameOwner = false;
        foreach ($plotToMerge->getPlotOwners() as $plotOwner) {
            if ($plot->isPlotOwner($plotOwner->getPlayerUUID())) {
                $hasSameOwner = true;
                break;
            }
        }
        if (!$hasSameOwner) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notSamePlotOwner"));
            return;
        }

        /** @var BooleanAttribute $flag */
        $flag = $plotToMerge->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.secondPlotServerPlotFlag", [$flag->getID()]));
            return;
        }

        $economyProvider = EconomyManager::getInstance()->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = EconomyManager::getInstance()->getMergePrice();
            if ($price > 0.0) {
                $money = yield from $economyProvider->awaitMoney($sender);
                if (!is_float($money)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("merge.loadMoneyError"));
                    return;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notEnoughMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price), $economyProvider->parseMoneyToString($price - $money)]));
                    return;
                }
                yield from $economyProvider->awaitMoneyRemoval($sender, $price);
                $sender->sendMessage($this->getPrefix() . $this->translateString("merge.chargedMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price)]));
            }
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("merge.start"));
        $world = $sender->getWorld();
        $task = new PlotMergeAsyncTask($world, $worldSettings, $plot, $plotToMerge);
        $task->setCallback(
            static function (int $elapsedTime, string $elapsedTimeString, mixed $result) use ($world, $plot, $sender) {
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
                if ($sender->isConnected()) {
                    $sender->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("merge.finish", [$elapsedTimeString]));
                }
            }
        );
        yield from $plot->merge($plotToMerge);
        yield from DataProvider::getInstance()->deletePlot($plotToMerge);
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("merge.deleteError", [$error->getMessage()]));
    }
}