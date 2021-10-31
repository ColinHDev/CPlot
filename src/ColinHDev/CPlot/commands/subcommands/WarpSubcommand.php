<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\plots\BasePlot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class WarpSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.senderNotOnline"));
            return;
        }

        switch (count($args)) {
            case 1:
                $plotKeys = explode(";", $args[0]);
                switch (count($plotKeys)) {
                    case 2:
                        if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
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
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
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

        if ($this->getPlugin()->getProvider()->getWorld($worldName) === null) {
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

        $plot = (new BasePlot($worldName, (int) $x, (int) $z))->toPlot();
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("warp.loadPlotError"));
            return;
        }


        if (!$sender->hasPermission("cplot.admin.warp")) {
            try {
                if (!$plot->hasPlotOwner()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("warp.noPlotOwner"));
                    return;
                }
            } catch (PlotException) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("warp.loadPlotPlayersError"));
                return;
            }
        }

        try {
            if ($plot->teleportTo($sender)) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("warp.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
                return;
            }
        } catch (PlotException) {
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("warp.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}