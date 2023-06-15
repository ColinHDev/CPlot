<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\lock\BorderChangeLockID;
use ColinHDev\CPlot\plots\lock\PlotLockManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlot\utils\ParseUtils;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use SOFe\AwaitGenerator\Await;
use function array_map;
use function array_merge;
use function count;
use function implode;

class BorderSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "border.senderNotOnline"]);
            return;
        }

        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "border.usage"]);
            return;
        }
        $block = ParseUtils::parseBlockFromString($args[0]);
        if ($block === null) {
            self::sendMessage($sender, ["prefix", "border.invalidBlock" => $args[0]]);
            return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "border.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "border.noPlot"]);
            return;
        }
        if (!$sender->hasPermission("cplot.admin.border")) {
            if (!$plot->hasPlotOwner()) {
                self::sendMessage($sender, ["prefix", "border.noPlotOwner"]);
                return;
            }
            if (!$plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "border.notPlotOwner"]);
                return;
            }
        }

        $lock = new BorderChangeLockID();
        if (!PlotLockManager::getInstance()->lockPlotsSilent($lock, $plot)) {
            self::sendMessage($sender, ["prefix", "border.plotLocked"]);
            return;
        }

        self::sendMessage($sender, ["prefix", "border.start"]);
        /** @phpstan-var PlotBorderChangeAsyncTask $task */
        $task = yield from Await::promise(
            static fn($resolve) => $plot->setBorderBlock($block, $resolve)
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
            "Changing plot border to " . $block->getName() . " in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $task->getElapsedTime() . "ms) for player " . $sender->getUniqueId()->getBytes() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
        );
        self::sendMessage($sender, ["prefix", "border.finish" => [$elapsedTimeString, $block->getName()]]);
        PlotLockManager::getInstance()->unlockPlots($lock, $plot);
    }
}