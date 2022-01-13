<?php

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\CPlot;
use ColinHDev\CPlot\events\CPlotEnterEvent;
use ColinHDev\CPlot\events\CPlotLeaveEvent;
use ColinHDev\CPlot\ResourceManager;
use ColinHDev\CPlotAPI\players\settings\SettingIDs;
use ColinHDev\CPlotAPI\players\utils\PlayerDataException;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use Ramsey\Uuid\Uuid;

class PlayerMoveListener implements Listener {

    public function onPlayerMove(PlayerMoveEvent $event) : void {
        if ($event->isCancelled()) {
            return;
        }

        $toPosition = $event->getTo();
        $worldSettings = CPlot::getInstance()->getProvider()->getWorld($toPosition->getWorld()->getFolderName());
        if ($worldSettings === null) {
            return;
        }

        $player = $event->getPlayer();
        $playerUUID = $player->getUniqueId()->toString();
        $plotTo = Plot::fromPosition($toPosition);
        $plotFrom = Plot::fromPosition($event->getFrom());

        if ($plotTo !== null) {
            // check if player is denied and hasn't bypass permission
            if (!$player->hasPermission("cplot.bypass.deny")) {
                try {
                    if ($plotTo->isPlotDenied($playerUUID)) {
                        if ($plotFrom === null) {
                            $event->cancel();
                            return;
                        } else {
                            $plotTo->teleportTo($player, false, false);
                            return;
                        }
                    }
                } catch (PlotException) {
                    $event->cancel();
                    return;
                }
            }

            // flags on plot enter
            if ($plotFrom === null) {
                $ev = new CPlotEnterEvent($plotFrom, $player);
                $ev->call();
                if ($ev->isCancelled()) return; //TODO: cancel event or teleport???

                // settings on plot enter
                try {
                    $playerData = CPlot::getInstance()->getProvider()->getPlayerDataByUUID($playerUUID);
                    if ($playerData !== null) {
                        foreach ($plotTo->getFlags() as $flag) {
                            $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_WARN_FLAG . $flag->getID());
                            if ($setting === null) {
                                continue;
                            }
                            foreach ($setting->getValue() as $value) {
                                if ($value === $flag->getValue()) {
                                    $player->sendMessage(
                                        ResourceManager::getInstance()->getPrefix() .
                                        ResourceManager::getInstance()->translateString(
                                            "player.move.setting.warn_flag",
                                            [$flag->getID(), $flag->toString()]
                                        )
                                    );
                                    continue 2;
                                }
                            }
                        }
                    }
                } catch (PlayerDataException | PlotException) {
                }

                // title flag && message flag
                try {
                    $title = "";
                    $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_TITLE);
                    if ($flag->getValue() === true) {
                        $title .= ResourceManager::getInstance()->translateString(
                            "player.move.plotEnter.title.coordinates",
                            [$plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]
                        );
                        if ($plotTo->hasPlotOwner()) {
                            $plotOwners = [];
                            foreach ($plotTo->getPlotOwners() as $plotOwner) {
                                $plotOwnerData = CPlot::getInstance()->getProvider()->getPlayerDataByUUID($plotOwner->getPlayerUUID());
                                $plotOwners[] = $plotOwnerData?->getPlayerName() ?? "ERROR:" . $plotOwner->getPlayerUUID();
                            }
                            $title .= ResourceManager::getInstance()->translateString(
                                "player.move.plotEnter.title.owner",
                                [
                                    implode(ResourceManager::getInstance()->translateString("player.move.plotEnter.title.owner.separator"), $plotOwners)
                                ]
                            );
                        }
                    }
                    $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_MESSAGE);
                    if ($flag->getValue() !== "") {
                        $title .= ResourceManager::getInstance()->translateString(
                            "player.move.plotEnter.title.flag.message",
                            [$flag->getValue()]
                        );
                    }
                    $player->sendTip($title);
                } catch (PlotException) {
                }

                // plot_enter flag
                try {
                    $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_PLOT_ENTER);
                    if ($flag->getValue() === true) {
                        foreach ($plotTo->getPlotOwners() as $plotOwner) {
                            $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($plotOwner->getPlayerUUID()));
                            $owner?->sendMessage(
                                ResourceManager::getInstance()->getPrefix() .
                                ResourceManager::getInstance()->translateString(
                                    "player.move.plotEnter.flag.plot_enter",
                                    [$player->getName(), $plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]
                                )
                            );
                        }
                    }
                } catch (PlotException) {
                }

                // TODO: check_offlinetime flag && offline system
            }
        }

        // plot leave
        if ($plotFrom !== null && $plotTo === null) {
            $ev = new CPlotLeaveEvent($plotFrom, $player);
            $ev->call();
            if ($ev->isCancelled()) return; //TODO: cancel event or teleport???

            // plot_leave flag
            try {
                $flag = $plotFrom->getFlagNonNullByID(FlagIDs::FLAG_PLOT_LEAVE);
                if ($flag->getValue() === true) {
                    foreach ($plotFrom->getPlotOwners() as $plotOwner) {
                        $owner = $player->getServer()->getPlayerByUUID(Uuid::fromString($plotOwner->getPlayerUUID()));
                        $owner?->sendMessage(
                            ResourceManager::getInstance()->getPrefix() .
                            ResourceManager::getInstance()->translateString(
                                "player.move.plotLeave.flag.plot_leave",
                                [$player->getName(), $plotFrom->getWorldName(), $plotFrom->getX(), $plotFrom->getZ()]
                            )
                        );
                    }
                }
            } catch (PlotException) {
            }
        }
    }
}