<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MiddleSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "middle.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "middle.noPlotWorld"]);
            return;
        }
        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "middle.noPlot"]);
            return;
        }

        if (!$plot->teleportTo($sender, TeleportDestination::PLOT_CENTER)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "middle.couldNotTeleport"]);
            return;
        }
        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "middle.success"]);
    }
}