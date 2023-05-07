<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\flags\InternalFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function implode;

class InfoSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "info.senderNotOnline"]);
            return;
        }

        $position = $sender->getPosition();
        $world = $position->getWorld();
        if (!((yield DataProvider::getInstance()->awaitWorld($world->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "info.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($position);
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "info.noPlot"]);
            return;
        }
        
        $owners = [];
        foreach($plot->getPlotOwners() as $plotPlayer) {
            $owners[] = self::translateForCommandSender($sender, ["format.list.player" => $plotPlayer->getPlayerData()->getPlayerName() ?? "Unknown"]);
        }
        $trusted = [];
        foreach($plot->getPlotTrusted() as $plotPlayer) {
            $trusted[] = self::translateForCommandSender($sender, ["format.list.player" => $plotPlayer->getPlayerData()->getPlayerName() ?? "Unknown"]);
        }
        $helpers = [];
        foreach($plot->getPlotHelpers() as $plotPlayer) {
            $helpers[] = self::translateForCommandSender($sender, ["format.list.player" => $plotPlayer->getPlayerData()->getPlayerName() ?? "Unknown"]);
        }
        $denied = [];
        foreach($plot->getPlotDenied() as $plotPlayer) {
            $denied[] = self::translateForCommandSender($sender, ["format.list.player" => $plotPlayer->getPlayerData()->getPlayerName() ?? "Unknown"]);
        }

        $flags = [];
        foreach($plot->getFlags() as $flagID => $flag) {
            if (!$flag instanceof InternalFlag) {
                $flags[] = self::translateForCommandSender($sender, ["format.list.flagWithValue" => [$flagID, $flag->toReadableString()]]);
            }
        }

        $playerSeparator = self::translateForCommandSender($sender, "format.list.player.separator");
        
        self::sendMessage(
            $sender,
            [
                "prefix", 
                "info.success" => [
                    $plot->getWorldName(), 
                    $plot->getX(), 
                    $plot->getZ(),
                    implode($playerSeparator, $owners),
                    $plot->getAlias() ?? "---",
                    BiomeSubcommand::getBiomeNameByID($world->getBiomeId($position->getFloorX(), $position->getFloorY(), $position->getFloorZ())),
                    implode($playerSeparator, $trusted),
                    implode($playerSeparator, $helpers),
                    implode($playerSeparator, $denied),
                    implode(self::translateForCommandSender($sender, "format.list.flagWithValue.separator"), $flags),
                ]
            ]
        );
    }
}