<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\tasks\async\PlotBorderChangeAsyncTask;
use ColinHDev\CPlotAPI\BasePlot;
use pocketmine\command\CommandSender;
use pocketmine\permission\Permission;
use pocketmine\player\Player;
use pocketmine\Server;

class ClaimSubcommand extends Subcommand {

    /**
     * @param CommandSender $sender
     * @param array         $args
     */
    public function execute(CommandSender $sender, array $args) : void {
        if (!$sender instanceof Player) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.senderNotOnline"));
            return;
        }

        $worldSettings = $this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName());
        if ($worldSettings === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.noPlotWorld"));
            return;
        }

        $basePlot = BasePlot::fromPosition($sender->getPosition());
        $plot = $basePlot?->toPlot();
        if ($basePlot === null || $plot === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.noPlot"));
            return;
        }
        if (!$plot->loadMergedPlots()) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.couldntLoadMergedPlots"));
            return;
        }
        $senderUUID = $sender->getUniqueId()->toString();
        if ($plot->getOwnerUUID() !== null) {
            if ($senderUUID !== $plot->getOwnerUUID()) {
                $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotAlreadyClaimed", [$this->getPlugin()->getProvider()->getPlayerNameByUUID($plot->getOwnerUUID()) ?? "ERROR"]));
                return;
            }
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotAlreadyClaimedBySender"));
            return;
        }

        $claimedPlots = $this->getPlugin()->getProvider()->getPlotsByOwnerUUID($senderUUID);
        if ($claimedPlots === null) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.loadClaimedPlotsError"));
            return;
        }
        $claimedPlotsCount = count($claimedPlots);
        $maxPlots = $this->getMaxPlotsOfPlayer($sender);
        if ($claimedPlotsCount > $maxPlots) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.plotLimitReached", [$claimedPlotsCount, $maxPlots]));
            return;
        }

        $plot->setOwnerUUID($senderUUID);
        $plot->setClaimTime(time());
        if (!$this->getPlugin()->getProvider()->savePlot($plot)) {
            $sender->sendMessage($this->getPrefix() . $this->translateString("claim.saveError"));
            return;
        }

        $blockBorderOnClaim = $worldSettings->getBlockBorderOnClaim();
        $task = new PlotBorderChangeAsyncTask($worldSettings, $plot, $blockBorderOnClaim);
        $world = $sender->getWorld();
        $task->setWorld($world);
        $task->setClosure(
            function (int $elapsedTime, string $elapsedTimeString, array $result) use ($world, $sender, $blockBorderOnClaim) {
                [$plotCount, $plots] = $result;
                $plots = array_map(
                    function (BasePlot $plot) : string {
                        return $plot->toSmallString();
                    },
                    $plots
                );
                Server::getInstance()->getLogger()->debug(
                    "Changing plot border due to plot claim to " . $blockBorderOnClaim->getName() . " (" . $blockBorderOnClaim->getId() . ":" . $blockBorderOnClaim->getMeta() . ", FullID: " . $blockBorderOnClaim->getFullId() . ") in world " . $world->getDisplayName() . " (folder: " . $world->getFolderName() . ") took " . $elapsedTimeString . " (" . $elapsedTime . "ms) for player " . $sender->getUniqueId()->toString() . " (" . $sender->getName() . ") for " . $plotCount . " plot" . ($plotCount > 1 ? "s" : "") . ": [" . implode(", ", $plots) . "]."
                );
            }
        );
        $this->getPlugin()->getServer()->getAsyncPool()->submitTask($task);

        $sender->sendMessage($this->getPrefix() . $this->translateString("claim.success", [$plot->toString(), $plot->toSmallString()]));
    }

    /**
     * @param Player $player
     * @return int
     */
    private function getMaxPlotsOfPlayer(Player $player) : int {
        if ($player->hasPermission("cplot.claimPlots.unlimited")) return PHP_INT_MAX;

        $player->recalculatePermissions();
        $permissions = $player->getEffectivePermissions();
        $permissions = array_filter(
            $permissions,
            function(string $name) : bool {
                return (str_starts_with($name, "cplot.claimPlots."));
            },
            ARRAY_FILTER_USE_KEY
        );
        if (count($permissions) === 0) return 0;

        krsort($permissions, SORT_FLAG_CASE | SORT_NATURAL);
        /** @var string $permissionName */
        /** @var Permission $permission */
        foreach ($permissions as $permissionName => $permission) {
            $maxPlots = substr($permissionName, 17);
            if (!is_numeric($maxPlots)) continue;
            return (int) $maxPlots;
        }
        return 0;
    }
}