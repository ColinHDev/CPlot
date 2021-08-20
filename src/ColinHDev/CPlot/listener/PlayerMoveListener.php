<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\flags\FlagIDs;
use ColinHDev\CPlotAPI\Plot;
use ColinHDev\CPlotAPI\PlotPlayer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use Ramsey\Uuid\Uuid;

class PlayerMoveListener implements Listener {

    public function onPlayerMove(PlayerMoveEvent $event) : void {
        if ($event->isCancelled()) return;

        $player = $event->getPlayer();
        $playerUUID = $event->getPlayer()->getUniqueId()->toString();

        $plotTo = Plot::fromPosition($event->getTo());
        $plotFrom = Plot::fromPosition($event->getFrom());

        if ($plotTo !== null) {
            // check if player is denied and hasn't bypass permission
            if (!$player->hasPermission("cplot.bypass.deny")) {
                $plotTo->loadPlotPlayers();
                $plotPlayer = $plotTo->getPlotPlayer($playerUUID);
                if ($plotPlayer !== null && $plotPlayer->getState() === PlotPlayer::STATE_DENIED) {
                    if ($plotFrom === null) {
                        $event->cancel();
                        return;
                    } else {
                        $plotTo->teleportTo($player);
                        return;
                    }
                }
            }

            // flags on plot enter
            if ($plotFrom === null) {
                // title flag && message flag
                // TODO: Settings on plot enter
                $plotTo->loadFlags();
                $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_TITLE);
                if ($flag !== null && $flag->getValueNonNull() === true) {
                    $title = ResourceManager::getInstance()->translateString(
                        "player.move.plotEnter.title.coordinates",
                        [$plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]
                    );
                    if ($plotTo->getOwnerUUID() !== null) {
                        $title .= ResourceManager::getInstance()->translateString(
                            "player.move.plotEnter.title.owner",
                            [CPlot::getInstance()->getProvider()->getPlayerNameByUUID($plotTo->getOwnerUUID()) ?? "ERROR"]
                        );
                    }
                    $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_MESSAGE);
                    if ($flag !== null && $flag->getValueNonNull() !== "") {
                        $title .= ResourceManager::getInstance()->translateString(
                            "player.move.plotEnter.title.flag.message",
                            [$flag->getValueNonNull()]
                        );
                    }
                    $player->sendTip($title);
                }

                // plot_enter flag
                if ($plotTo->getOwnerUUID() !== null) {
                    $plotTo->loadFlags();
                    $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_PLOT_ENTER);
                    if ($flag !== null && $flag->getValueNonNull() === true) {
                        $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($plotTo->getOwnerUUID()));
                        if ($owner !== null) {
                            $owner->sendMessage(
                                ResourceManager::getInstance()->getPrefix() .
                                ResourceManager::getInstance()->translateString(
                                    "player.move.plotEnter.flag.plot_enter",
                                    [$player->getName(), $plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]
                                )
                            );
                        }
                    }
                }

                // TODO: check_offlinetime flag && offline system
            }
        }

        // plot leave
        if ($plotFrom !== null && $plotTo === null) {
            // plot_leave flag
            if ($plotFrom->getOwnerUUID() !== null) {
                $plotFrom->loadFlags();
                $flag = $plotFrom->getFlagNonNullByID(FlagIDs::FLAG_PLOT_LEAVE);
                if ($flag !== null && $flag->getValueNonNull() === true) {
                    $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($plotFrom->getOwnerUUID()));
                    if ($owner !== null) {
                        $owner->sendMessage(
                            ResourceManager::getInstance()->getPrefix() .
                            ResourceManager::getInstance()->translateString(
                                "player.move.plotLeave.flag.plot_leave",
                                [$player->getName(), $plotFrom->getWorldName(), $plotFrom->getX(), $plotFrom->getZ()]
                            )
                        );
                    }
                }
            }
        }
    }
}