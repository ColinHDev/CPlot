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
use ColinHDev\CPlot\tasks\async\PlotClearAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

/**
 * @phpstan-extends Subcommand<null>
 */
class ClearSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.senderNotOnline"));
            return;
        }

        $worldSettings = yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlotWorld"));
            return;
        }

        $plot = yield from Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlot"));
            return;
        }

        if (!$sender->hasPermission("cplot.admin.clear")) {
            if (!$plot->hasPlotOwner()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.noPlotOwner"));
                return;
            }
            if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.notPlotOwner"));
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("clear.serverPlotFlag", [$flag->getID()]));
            return;
        }

        $economyProvider = EconomyManager::getInstance()->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = EconomyManager::getInstance()->getClearPrice();
            if ($price > 0.0) {
                $money = yield from $economyProvider->awaitMoney($sender);
                if (!is_float($money)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("clear.loadMoneyError"));
                    return;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("clear.notEnoughMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price), $economyProvider->parseMoneyToString($price - $money)]));
                    return;
                }
                yield from $economyProvider->awaitMoneyRemoval($sender, $price);
                $sender->sendMessage($this->getPrefix() . $this->translateString("clear.chargedMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price)]));
            }
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("clear.start"));
        $world = $sender->getWorld();
        $task = new PlotClearAsyncTask($world, $worldSettings, $plot);
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
                    "Clearing plot" . ($plotCount > 1 ? "s" : "") . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
                if ($sender->isConnected()) {
                    $sender->sendMessage(ResourceManager::getInstance()->getPrefix() . ResourceManager::getInstance()->translateString("clear.finish", [$elapsedTimeString]));
                }
            }
        );
        Server::getInstance()->getAsyncPool()->submitTask($task);
    }
}