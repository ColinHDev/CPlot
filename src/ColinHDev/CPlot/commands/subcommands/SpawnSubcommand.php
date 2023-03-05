<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

class SpawnSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "spawn.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "spawn.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "spawn.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.spawn") && !$plot->isPlotOwner($sender)) {
            self::sendMessage($sender, ["prefix", "spawn.notPlotOwner"]);
            return;
        }

        $location = $sender->getLocation();
        $flag = Flags::SPAWN()->createInstance(Location::fromObject(
            $location->subtractVector($plot->getVector3()),
            $sender->getWorld(),
            $location->getYaw(),
            $location->getPitch()
        ));
        $plot->addFlag($flag);
        yield DataProvider::getInstance()->savePlotFlag($plot, $flag);
        self::sendMessage($sender, ["prefix", "spawn.success"]);
    }
}