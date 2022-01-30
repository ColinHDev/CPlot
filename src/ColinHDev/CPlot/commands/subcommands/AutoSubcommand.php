<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<null>
 */
class AutoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.senderNotOnline"));
            return null;
        }

        $worldName = $sender->getWorld()->getFolderName();
        $worldSettings = yield from DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotWorld"));
            return null;
        }

        /** @var Plot|null $plot */
        $plot = yield from DataProvider::getInstance()->awaitNextFreePlot($worldName, $worldSettings);
        if ($plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.noPlotFound"));
            return null;
        }

        if (!($plot->toBasePlot()->teleportTo($sender))) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("auto.teleportError", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
            return null;
        }

        $sender->sendMessage($this->getPrefix() . $this->translateString("auto.success", [$plot->getWorldName(), $plot->getX(), $plot->getZ()]));
        return null;
    }
}