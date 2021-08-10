<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AutoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.senderNotOnline"));
            return;
        }

        $world = $sender->getWorld();
        if ($this->getPlugin()->getProvider()->getWorld($world->getFolderName()) === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotWorld"));
            return;
        }

        $plot = $this->getPlugin()->getProvider()->getNextFreePlot($world->getFolderName());
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotFound"));
            return;
        }

        if (!$plot->toBasePlot()->teleportTo($sender)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            return;
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("auto.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}