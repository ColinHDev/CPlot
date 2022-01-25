<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class AutoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.senderNotOnline"));
            return;
        }

        $worldName = $sender->getWorld()->getFolderName();
        if (!((yield from DataProvider::getInstance()->awaitWorld($worldName)) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotWorld"));
            return;
        }

        /** @var Plot|null $plot */
        $plot = yield from DataProvider::getInstance()->awaitNextFreePlot($worldName);
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotFound"));
            return;
        }

        if (!(yield from $plot->toBasePlot()->teleportTo($sender))) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            return;
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("auto.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
    }
}