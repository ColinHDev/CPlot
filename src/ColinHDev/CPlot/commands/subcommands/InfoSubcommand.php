<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<null>
 */
class InfoSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "info.senderNotOnline"]);
            return null;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "info.noPlotWorld"]);
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition());
        if (!($plot instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "info.noPlot"]);
            return null;
        }

        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "info.plot" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]);

        $plotOwnerData = [];
        foreach ($plot->getPlotOwners() as $plotOwner) {
            $playerData = $plotOwner->getPlayerData();
            /** @phpstan-var string $addTime */
            $addTime = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $sender,
                ["info.owners.time.format" => explode(".", date("d.m.Y.H.i.s", $plotOwner->getAddTime()))]
            );
            $plotOwnerData[] = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $sender,
                ["info.owners.list" => [
                    $playerData->getPlayerName() ?? "Error: " . ($playerData->getPlayerXUID() ?? $playerData->getPlayerUUID() ?? $playerData->getPlayerID()),
                    $addTime
                ]]
            );
        }
        if (count($plotOwnerData) === 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.owners.none"]);
        } else {
            /** @phpstan-var string $separator */
            $separator = yield LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "info.owners.list.separator");
            $list = implode($separator, $plotOwnerData);
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                $sender,
                ["info.owners" => $list]
            );
        }

        if ($plot->getAlias() !== null) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.plotAlias" => $plot->getAlias()]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.plotAlias.none"]);
        }

        $mergedPlotsCount = count($plot->getMergePlots());
        if ($mergedPlotsCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.merges" => $mergedPlotsCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.merges.none"]);
        }

        $trustedCount = count($plot->getPlotTrusted());
        if ($trustedCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.trusted" => $trustedCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.trusted.none"]);
        }
        $helpersCount = count($plot->getPlotHelpers());
        if ($helpersCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.helpers" => $helpersCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.helpers.none"]);
        }
        $deniedCount = count($plot->getPlotDenied());
        if ($deniedCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.denied" => $deniedCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.denied.none"]);
        }

        $flagsCount = count($plot->getFlags());
        if ($flagsCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.flags" => $flagsCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.flags.none"]);
        }

        $ratesCount = count($plot->getPlotRates());
        if ($ratesCount > 0) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.rates" => $ratesCount]);
        } else {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["info.rates.none"]);
        }
        return null;
    }
}