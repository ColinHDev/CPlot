<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

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
                        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.noPlotWorld"));
                            return;
                        }
                        $worldName = $sender->getWorld()->getFolderName();
                        $x = $plotKeys[0];
                        $z = $plotKeys[1];
                        break;

                    case 3:
                        $worldName = $plotKeys[0];
                        $x = $plotKeys[1];
                        $z = $plotKeys[2];
                        break;

                    default:
                        $sender->sendMessage($this->getPrefix() . $this->getUsage());
                        return;
                }
                break;

            case 2:
                if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("warp.noPlotWorld"));
                    return;
                }
                $worldName = $sender->getWorld()->getFolderName();
                $x = $args[0];
                $z = $args[1];
                break;

            case 3:
                $worldName = $args[0];
                $x = $args[1];
                $z = $args[2];
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                return;
        }

        if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
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

        $plot = yield from (new BasePlot($worldName, (int) $x, (int) $z))->toAsyncPlot();
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

        if (!(yield from $plot->teleportTo($sender))) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("warp.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}