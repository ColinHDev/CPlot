<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\InternalFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function array_filter;

class InfoSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "info.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "info.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "info.noPlot"]);
            return;
        }

        self::sendMessage($sender, ["prefix", "info.plot" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);

        $plotOwnerData = [];
        foreach ($plot->getPlotOwners() as $plotOwner) {
            $playerData = $plotOwner->getPlayerData();
            /** @phpstan-var string $addTime */
            $addTime = self::translateForCommandSender(
                $sender,
                ["info.owners.time.format" => explode(".", date("d.m.Y.H.i.s", $plotOwner->getAddTime()))]
            );
            $plotOwnerData[] = self::translateForCommandSender(
                $sender,
                ["info.owners.list" => [
                    $playerData->getPlayerName() ?? "Error: " . ($playerData->getPlayerXUID() ?? $playerData->getPlayerUUID() ?? $playerData->getPlayerID()),
                    $addTime
                ]]
            );
        }
        if (count($plotOwnerData) === 0) {
            self::sendMessage($sender, ["info.owners.none"]);
        } else {
            /** @phpstan-var string $separator */
            $separator = self::translateForCommandSender($sender, "info.owners.list.separator");
            $list = implode($separator, $plotOwnerData);
            self::sendMessage(
                $sender,
                ["info.owners" => $list]
            );
        }

        if ($plot->getAlias() !== null) {
            self::sendMessage($sender, ["info.plotAlias" => $plot->getAlias()]);
        } else {
            self::sendMessage($sender, ["info.plotAlias.none"]);
        }

        $mergedPlotsCount = count($plot->getMergePlots());
        if ($mergedPlotsCount > 0) {
            self::sendMessage($sender, ["info.merges" => $mergedPlotsCount]);
        } else {
            self::sendMessage($sender, ["info.merges.none"]);
        }

        $trustedCount = count($plot->getPlotTrusted());
        if ($trustedCount > 0) {
            self::sendMessage($sender, ["info.trusted" => $trustedCount]);
        } else {
            self::sendMessage($sender, ["info.trusted.none"]);
        }
        $helpersCount = count($plot->getPlotHelpers());
        if ($helpersCount > 0) {
            self::sendMessage($sender, ["info.helpers" => $helpersCount]);
        } else {
            self::sendMessage($sender, ["info.helpers.none"]);
        }
        $deniedCount = count($plot->getPlotDenied());
        if ($deniedCount > 0) {
            self::sendMessage($sender, ["info.denied" => $deniedCount]);
        } else {
            self::sendMessage($sender, ["info.denied.none"]);
        }

        $flagsCount = count(
            array_filter(
                $plot->getFlags(),
                static function(Flag $flag) : bool {
                    return !($flag instanceof InternalFlag);
                }
            )
        );
        if ($flagsCount > 0) {
            self::sendMessage($sender, ["info.flags" => $flagsCount]);
        } else {
            self::sendMessage($sender, ["info.flags.none"]);
        }

        $ratesCount = count($plot->getPlotRates());
        if ($ratesCount > 0) {
            self::sendMessage($sender, ["info.rates" => $ratesCount]);
        } else {
            self::sendMessage($sender, ["info.rates.none"]);
        }
    }
}