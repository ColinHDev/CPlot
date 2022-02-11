<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\PlotPlayer;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\EconomyManager;
use ColinHDev\CPlot\provider\EconomyProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<null>
 */
class ClaimSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.senderNotOnline"));
            return null;
        }

        if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.noPlotWorld"));
            return null;
        }

        $plot = yield Plot::awaitFromPosition($sender->getPosition(), false);
        if (!($plot instanceof Plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.noPlot"));
            return null;
        }

        $senderUUID = $sender->getUniqueId()->getBytes();
        if ($plot->hasPlotOwner()) {
            if ($plot->isPlotOwner($senderUUID)) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotAlreadyClaimedBySender"));
                return null;
            }
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotAlreadyClaimed"));
            return null;
        }

        /** @phpstan-var array<string, Plot> $claimedPlots */
        $claimedPlots = yield DataProvider::getInstance()->awaitPlotsByPlotPlayer($senderUUID, PlotPlayer::STATE_OWNER);
        $claimedPlotsCount = count($claimedPlots);
        $maxPlots = $this->getMaxPlotsOfPlayer($sender);
        if ($claimedPlotsCount > $maxPlots) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotLimitReached", [$claimedPlotsCount, $maxPlots]));
            return null;
        }

        $economyProvider = EconomyManager::getInstance()->getProvider();
        if ($economyProvider instanceof EconomyProvider) {
            $price = EconomyManager::getInstance()->getClaimPrice();
            if ($price > 0.0) {
                $money = yield $economyProvider->awaitMoney($sender);
                if (!is_float($money)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("claim.loadMoneyError"));
                    return null;
                }
                if ($money < $price) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("claim.notEnoughMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price), $economyProvider->parseMoneyToString($price - $money)]));
                    return null;
                }
                yield $economyProvider->awaitMoneyRemoval($sender, $price);
                $sender->sendMessage($this->getPrefix() . $this->translateString("claim.chargedMoney", [$economyProvider->getCurrency(), $economyProvider->parseMoneyToString($price)]));
            }
        }

        $senderData = new PlotPlayer($senderUUID, PlotPlayer::STATE_OWNER);
        $plot->addPlotPlayer($senderData);
        yield DataProvider::getInstance()->savePlot($plot);
        yield DataProvider::getInstance()->savePlotPlayer($plot, $senderData);

        $sender->sendMessage($this->getPrefix() . $this->translateString("claim.success", [$plot->toString(), $plot->toSmallString()]));
        return null;
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("claim.saveError", [$error->getMessage()]));
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