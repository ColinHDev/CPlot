<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

class SpawnSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "spawn.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "spawn.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "spawn.noPlot"]);
            return;
        }

        if (!$sender->hasPermission("cplot.admin.spawn") && !$plot->isPlotOwner($sender)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "spawn.notPlotOwner"]);
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
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "spawn.success"]);
    }
}