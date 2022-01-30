<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\BasePlot;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<void>
 */
class WarpSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.senderNotOnline"));
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
                        $sender->sendMessage($this->getPrefix() . $this->getUsage());
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
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                return;
        }

        $worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.invalidPlotWorld", [$worldName]));
            return;
        }
        if (!is_numeric($x)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.invalidXCoordinate", [$x]));
            return;
        }
        if (!is_numeric($z)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.invalidZCoordinate", [$z]));
            return;
        }

        $plot = yield from (new BasePlot($worldName, $worldSettings, (int) $x, (int) $z))->toAsyncPlot();
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.loadPlotError"));
            return;
        }

        if (!$sender->hasPermission("cplot.admin.warp")) {
            if (!$plot->hasPlotOwner()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("warp.noPlotOwner"));
                return;
            }
        }

        if (!($plot->teleportTo($sender))) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("warp.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}