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
use ColinHDev\CPlot\tasks\async\PlotResetAsyncTask;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;

class ResetSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.senderNotOnline"]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.reset")) {
            if (!$plot->hasPlotOwner()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($sender)) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.notPlotOwner"]);
                return;
            }
        }

        /** @var BooleanAttribute $flag */
        $flag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
        if ($flag->getValue() === true) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.serverPlotFlag" => $flag->getID()]);
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
                    $errorMessage = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, $exception->getLanguageKey());
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                        $sender, [
                            "prefix",
                            "reset.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($price),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                    return;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.start"]);
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
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "reset.finish" => $elapsedTimeString]);
    }
}