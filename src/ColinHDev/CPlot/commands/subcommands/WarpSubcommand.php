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

/**
 * @phpstan-extends Subcommand<null>
 */
class WarpSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.senderNotOnline"]);
            return null;
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
                        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.usage"]);
                        return null;
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
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.usage"]);
                return null;
        }

        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidPlotWorld" => $worldName]);
            return null;
        }
        if (!is_numeric($x)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidXCoordinate" => $x]);
            return null;
        }
        if (!is_numeric($z)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.invalidZCoordinate" => $z]);
            return null;
        }

        $plot = yield (new BasePlot($worldName, $worldSettings, (int) $x, (int) $z))->toAsyncPlot();
        if (!($plot instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.loadPlotError"]);
            return null;
        }

        if (!$sender->hasPermission("cplot.admin.warp")) {
            if (!$plot->hasPlotOwner()) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.noPlotOwner"]);
                return null;
            }
        }

        if (!($plot->teleportTo($sender))) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
            return null;
        }
        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "warp.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
        return null;
    }
}