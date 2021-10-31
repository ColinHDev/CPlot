<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\tasks\async\PlotResetAsyncTask;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

class ResetSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.senderNotOnline"));
            return;
        }

        $worldSettings = $this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.noPlotWorld"));
            return;
        }

        $plot = Plot::fromPosition($sender->getPosition());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.noPlot"));
            return;
        }
        try {
            $plot->loadMergePlots();
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.loadMergedPlotsError"));
            return;
        }
        if (!$sender->hasPermission("cplot.admin.reset")) {
            try {
                if (!$plot->hasPlotOwner()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.noPlotOwner"));
                    return;
                }
                if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.notPlotOwner"));
                    return;
                }
            } catch (PlotException) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("reset.loadPlotPlayersError"));
                return;
            }
        }

        try {
            $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        } catch (PlotException) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.loadFlagsError"));
            return;
        }
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.serverPlotFlag", [$flag->getID()]));
            return;
        }

        $economyProvider = $this->getPlugin()->getEconomyProvider();
        if ($economyProvider !== null) {
            $price = $economyProvider->getPrice(EconomyProvider::PRICE_RESET) ?? 0.0;
            if ($price > 0.0) {
                $money = $economyProvider->getMoney($sender);
                if ($money === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.loadMoneyError"));
                    return;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.notEnoughMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price), $economyProvider->parseMoneyToString($price - $money)]));
                    return;
                }
                if (!$economyProvider->removeMoney($sender, $price, "Paid " . $price . $economyProvider->getCurrency() . " to reset the plot " . $plot->toString() . ".")) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.saveMoneyError"));
                    return;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("reset.chargedMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price)]));
            }
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("reset.start"));
        $task = new PlotResetAsyncTask($worldSettings, $plot);
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
                    "Resetting plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->toString() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if ($sender->isConnected()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("reset.finish", [$elapsedTimeString]));
                }
            }
        );
        if (!$this->getPlugin()->getProvider()->deletePlot($plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("reset.deletePlotError"));
            return;
        }
        $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);
    }
}