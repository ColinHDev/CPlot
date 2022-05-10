<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class ClaimSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.senderNotOnline"]);
            return null;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.noPlotWorld"]);
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition(), false);
        if (!($plot instanceof Plot)) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.noPlot"]);
            return null;
        }

        if ($plot->hasPlotOwner()) {
            if ($plot->isPlotOwner($sender)) {
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.plotAlreadyClaimedBySender"]);
                return null;
            }
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.plotAlreadyClaimed"]);
            return null;
        }

        $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
        if (!($playerData instanceof PlayerData)) {
            return null;
        }
        /** @phpstan-var array<string, Plot> $claimedPlots */
        $claimedPlots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
        $claimedPlotsCount = count($claimedPlots);
        $maxPlots = $this->getMaxPlotsOfPlayer($sender);
        if ($claimedPlotsCount > $maxPlots) {
            yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.plotLimitReached" => [$claimedPlotsCount, $maxPlots]]);
            return null;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getClaimPrice();
            if ($price > 0.0) {
                yield from $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getClaimReason());
                yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        $senderData = new PlotPlayer($playerData, PlotPlayer::STATE_OWNER);
        $plot->addPlotPlayer($senderData);
        yield DataProvider::getInstance()->savePlotPlayer($plot, $senderData);

        yield LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "claim.success" => [$plot->toString(), $plot->toSmallString()]]);
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        if ($error instanceof EconomyException) {
            LanguageManager::getInstance()->getProvider()->translateForCommandSender(
                $sender,
                $error->getLanguageKey(),
                static function(string $errorMessage) use($sender) : void {
                    $economyManager = EconomyManager::getInstance();
                    $economyProvider = $economyManager->getProvider();
                    // This exception should not be thrown if no economy provider is set.
                    assert($economyProvider instanceof EconomyProvider);
                    LanguageManager::getInstance()->getProvider()->sendMessage(
                        $sender,
                        [
                            "prefix",
                            "claim.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($economyManager->getClaimPrice()),
                                $economyProvider->getCurrency(),
                                $errorMessage
                            ]
                        ]
                    );
                }
            );
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "claim.saveError" => $error->getMessage()]);
    }

    private function getMaxPlotsOfPlayer(Player $player) : int {
        if ($player->hasPermission("cplot.claimPlots.unlimited")) {
            return PHP_INT_MAX;
        }

        $player->recalculatePermissions();
        $permissions = $player->getEffectivePermissions();
        $permissions = array_filter(
            $permissions,
            static function (string $name) : bool {
                return (str_starts_with($name, "cplot.claimPlots."));
            },
            ARRAY_FILTER_USE_KEY
        );
        if (count($permissions) === 0) {
            return 0;
        }

        krsort($permissions, SORT_FLAG_CASE | SORT_NATURAL);
        /** @var string $permissionName */
        /** @var Permission $permission */
        foreach ($permissions as $permissionName => $permission) {
            $maxPlots = substr($permissionName, 17);
            if (!is_numeric($maxPlots)) {
                continue;
            }
            return (int) $maxPlots;
        }
        return 0;
    }
}