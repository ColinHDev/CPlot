<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\tasks\async\PlotMergeAsyncTask;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use pocketmine\command\CommandSender;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\Server;

class MergeSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.senderNotOnline"));
            return;
        }

        $worldSettings = $this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlotWorld"));
            return;
        }
        $basePlot = BasePlot::fromPosition($sender->getPosition());
        $plot = $basePlot?->toPlot();
        if ($basePlot === null || $plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlot"));
            return;
        }
        if ($plot->getOwnerUUID() === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.noPlotOwner"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.merge")) {
            if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
        }
        $plot->loadFlags();
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.serverPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        $rotation = ($sender->getLocation()->getYaw() - 180) % 360 ;
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

        $plotToMerge = $basePlot->getSide($direction)?->toPlot();
        if ($plotToMerge === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.invalidSecondPlot"));
            return;
        }

        if ($plot->isSame($plotToMerge)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.alreadyMerged"));
            return;
        }
        if ($plot->getOwnerUUID() !== $plotToMerge->getOwnerUUID()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notSamePlotOwner"));
            return;
        }

        $flag = $plotToMerge->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.secondPlotServerPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        if (!$plot->loadMergedPlots()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.loadMergedPlotsError"));
            return;
        }
        if (!$plotToMerge->loadMergedPlots()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.loadMergedPlotsOfSecondPlotError"));
            return;
        }

        $economyProvider = $this->getPlugin()->getEconomyProvider();
        if ($economyProvider !== null) {
            $price = $economyProvider->getPrice(EconomyProvider::PRICE_MERGE) ?? 0.0;
            if ($price > 0.0) {
                $money = $economyProvider->getMoney($sender);
                if ($money === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("merge.loadMoneyError"));
                    return;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("merge.notEnoughMoney", [$economyProvider->getCurrency(), $price, ($price - $money)]));
                    return;
                }
                if (!$economyProvider->removeMoney($sender, $price, "Paid " . $price . $economyProvider->getCurrency() . " to merge the plot " . $plot->toString() . " with the plot " . $plotToMerge->toString() . ".")) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("merge.saveMoneyError"));
                    return;
                }
            }
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("merge.start"));
        $task = new PlotMergeAsyncTask($worldSettings, $plot, $plotToMerge);
        $world = $sender->getWorld();
        $task->setWorld($world);
        $task->setClosure(
            function (int $elapsedTime, string $elapsedTimeString, array $result) use ($world, $sender) {
                [$plotCount, $plots] = $result;
                $plots = array_map(
                    function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    $plots
                );
                Server::getInstance()->getLogger()->debug(
                    "Merging plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->toString() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if (!$sender->isConnected()) return;
                $sender->sendMessage($this->getPrefix() . $this->translateString("merge.finish", [$elapsedTimeString]));
            }
        );
        if (!$plot->merge($plotToMerge)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.saveError"));
            return;
        }
        if (!$this->getPlugin()->getProvider()->deletePlot($plotToMerge)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("merge.deleteError"));
            return;
        }
        $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);
    }
}