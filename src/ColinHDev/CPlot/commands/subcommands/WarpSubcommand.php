<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class WarpSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.senderNotOnline"]);
            return;
        }

        switch (count($args)) {
            case 1:
                $plotKeys = explode(";", $args[0]);
                switch (count($plotKeys)) {
                    case 2:
                        $worldName = $sender->getWorld()->getFolderName();
                        [$x, $z] = $plotKeys;
                        break;
                    case 3:
                        [$worldName, $x, $z] = $plotKeys;
                        break;
                    default:
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.usage"]);
                        return;
                }
                break;
            case 2:
                $worldName = $sender->getWorld()->getFolderName();
                [$x, $z] = $args;
                break;
            case 3:
                [$worldName, $x, $z] = $args;
                break;
            default:
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.usage"]);
                return;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidPlotWorld" => $worldName]);
            return;
        }
        if (!is_numeric($x)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidXCoordinate" => $x]);
            return;
        }
        if (!is_numeric($z)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidZCoordinate" => $z]);
            return;
        }

        $plot = yield (new BasePlot($worldName, $worldSettings, (int) $x, (int) $z))->toAsyncPlot();
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.loadPlotError"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.warp")) {
            if (!$plot->hasPlotOwner()) {
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.noPlotOwner"]);
                return;
            }
        }

        if (!($plot->teleportTo($sender))) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
            return;
        }
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
    }
}