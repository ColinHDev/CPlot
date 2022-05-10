<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class AutoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "auto.senderNotOnline"]);
            return null;
        }

        $worldName = $sender->getWorld()->getFolderName();
        $worldSettings = yield DataProvider::getInstance()->awaitWorld($worldName);
        if (!($worldSettings instanceof WorldSettings)) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "auto.noPlotWorld"]);
            return null;
        }

        /** @var Plot|null $plot */
        $plot = yield DataProvider::getInstance()->awaitNextFreePlot($worldName, $worldSettings);
        if ($plot === null) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "auto.noPlotFound"]);
            return null;
        }

        if (!($plot->toBasePlot()->teleportTo($sender))) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "auto.teleportError" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
            return null;
        }

        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "auto.success" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);
        return null;
    }
}