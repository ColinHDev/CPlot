<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\tasks\async\PlotClearAsyncTask;
use ColinHDev\CPlotAPI\BasePlot;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class ClearSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.senderNotOnline"));
            return;
        }

        $worldSettings = $this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlot"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.clear")) {
            if ($plot->getOwnerUUID() === null) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlotOwner"));
                return;
            } else if ($plot->getOwnerUUID() !== $sender->getUniqueId()->toString()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.notPlotOwner", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
        }

        if (!$plot->loadFlags()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.loadFlagsError"));
            return;
        }
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag === null || $flag->getValueNonNull() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.serverPlotFlag", [$flag->getID() ?? FlagIDs::FLAG_SERVER_PLOT]));
            return;
        }

        if (!$plot->loadMergedPlots()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.loadMergedPlotsError"));
            return;
        }

        $economyProvider = $this->getPlugin()->getEconomyProvider();
        if ($economyProvider !== null) {
            $price = $economyProvider->getPrice(EconomyProvider::PRICE_CLEAR) ?? 0.0;
            if ($price > 0.0) {
                $money = $economyProvider->getMoney($sender);
                if ($money === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("clear.loadMoneyError"));
                    return;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("clear.notEnoughMoney", [$economyProvider->getCurrency(), $price, ($price - $money)]));
                    return;
                }
                if (!$economyProvider->removeMoney($sender, $price, "Paid " . $price . $economyProvider->getCurrency() . " to clear the plot " . $plot->toString() . ".")) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("clear.saveMoneyError"));
                    return;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.chargedMoney", [$economyProvider->getCurrency(), $price]));
            }
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("clear.start"));
        $task = new PlotClearAsyncTask($worldSettings, $plot);
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
                    "Clearing plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->toString() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if (!$sender->isConnected()) return;
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.finish", [$elapsedTimeString]));
            }
        );
        $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);
    }
}