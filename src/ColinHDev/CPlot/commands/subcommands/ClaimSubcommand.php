<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\event\PlotClaimAsyncEvent;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\provider\utils\EconomyException;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\player\Player;
use poggit\libasynql\SqlError;

class ClaimSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            self::sendMessage($sender, ["prefix", "claim.senderNotOnline"]);
            return;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            self::sendMessage($sender, ["prefix", "claim.noPlotWorld"]);
            return;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition(), false);
        if (!($plot instanceof Plot)) {
            self::sendMessage($sender, ["prefix", "claim.noPlot"]);
            return;
        }

        if ($plot->hasPlotOwner()) {
            if ($plot->isPlotOwner($sender)) {
                self::sendMessage($sender, ["prefix", "claim.plotAlreadyClaimedBySender"]);
                return;
            }
            self::sendMessage($sender, ["prefix", "claim.plotAlreadyClaimed"]);
            return;
        }

        $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
        if (!($playerData instanceof PlayerData)) {
            return;
        }
        /** @phpstan-var array<string, Plot> $claimedPlots */
        $claimedPlots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($playerData->getPlayerID(), PlotPlayer::STATE_OWNER);
        $claimedPlotsCount = count($claimedPlots);
        foreach ($claimedPlots as $playerPlot) $claimedPlotsCount += count($playerPlot->getMergePlots());
        $maxPlots = $this->getMaxPlotsOfPlayer($sender);
        if ($claimedPlotsCount >= $maxPlots) {
            self::sendMessage($sender, ["prefix", "claim.plotLimitReached" => [$claimedPlotsCount, $maxPlots]]);
            return;
        }

        $economyManager = EconomyManager::getInstance();
        $economyProvider = $economyManager->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = $economyManager->getClaimPrice();
            if ($price > 0.0) {
                try {
                    yield from $economyProvider->awaitMoneyRemoval($sender, $price, $economyManager->getClaimReason());
                } catch(EconomyException $exception) {
                    self::sendMessage(
                        $sender, [
                            "prefix",
                            "claim.chargeMoneyError" => [
                                $economyProvider->parseMoneyToString($price),
                                $economyProvider->getCurrency(),
                                self::translateForCommandSender($sender, $exception->getLanguageKey())
                            ]
                        ]
                    );
                    return;
                }
                self::sendMessage($sender, ["prefix", "claim.chargedMoney" => [$economyProvider->parseMoneyToString($price), $economyProvider->getCurrency()]]);
            }
        }

        /** @phpstan-var PlotClaimAsyncEvent $event */
        $event = yield from PlotClaimAsyncEvent::create($plot, $sender);
        if ($event->isCancelled()) {
            return;
        }

        $senderData = new PlotPlayer($playerData, PlotPlayer::STATE_OWNER);
        $plot->addPlotPlayer($senderData);
        try {
            yield from DataProvider::getInstance()->savePlotPlayer($plot, $senderData);
        } catch (SqlError $exception) {
            self::sendMessage($sender, ["prefix", "claim.saveError" => $exception->getMessage()]);
            return;
        }
        self::sendMessage($sender, ["prefix", "claim.success" => [$plot->toString(), $plot->toSmallString()]]);
    }

    public function getMaxPlotsOfPlayer(Player $player) : int {
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
