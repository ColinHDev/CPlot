<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\PlayerInteractFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\block\Door;
use pocketmine\block\FenceGate;
use pocketmine\block\Trapdoor;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

class PlayerInteractListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        $position = $event->getBlock()->getPosition();
        /** @phpstan-var true|false|null $isPlotWorld */
        $isPlotWorld = $this->getAPI()->isPlotWorld($position->getWorld())->getResult();
        if ($isPlotWorld !== true) {
            if ($isPlotWorld !== false) {
                $event->cancel();
            }
            return;
        }

        /** @phpstan-var Plot|false|null $plot */
        $plot = $this->getAPI()->getOrLoadPlotAtPosition($position)->getResult();
        if ($plot instanceof Plot) {
            $player = $event->getPlayer();
            if ($player->hasPermission("cplot.interact.plot")) {
                return;
            }

            if ($plot->isPlotOwner($player)) {
                return;
            }
            if ($plot->isPlotTrusted($player)) {
                return;
            }
            if ($plot->isPlotHelper($player)) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner !== null) {
                        return;
                    }
                }
            }

            $block = $event->getBlock();
            if (
                ($block instanceof Door || $block instanceof Trapdoor || $block instanceof FenceGate) &&
                $plot->getFlag(Flags::PLAYER_INTERACT())->equals(PlayerInteractFlag::TRUE())
            ) {
                return;
            }
            if ($plot->getFlag(Flags::USE())->contains($block)) {
                return;
            }

        } else if ($plot === false) {
            if ($event->getPlayer()->hasPermission("cplot.interact.road")) {
                return;
            }
        }

        $event->cancel();
    }
}