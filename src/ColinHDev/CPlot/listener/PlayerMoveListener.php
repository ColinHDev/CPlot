<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\attributes\BooleanListAttribute;
use ColinHDev\CPlot\attributes\StringAttribute;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

class PlayerMoveListener implements Listener {

    public function onPlayerMove(PlayerMoveEvent $event) : void {
        $player = $event->getPlayer();

        $toPosition = $event->getTo();
        $worldSettings = DataProvider::getInstance()->loadWorldIntoCache($toPosition->getWorld()->getFolderName());
        if (!($worldSettings instanceof WorldSettings)) {
            return;
        }

        $plotTo = Plot::loadFromPositionIntoCache($toPosition);
        $plotFrom = Plot::loadFromPositionIntoCache($event->getFrom());
        if (!($plotTo instanceof Plot) || !($plotFrom instanceof Plot)) {
            return;
        }

        Await::f2c(
            static function () use ($player, $plotTo, $plotFrom) : \Generator {
                if ($plotTo instanceof Plot) {
                    // check if player is denied and hasn't bypass permission
                    if (!$player->hasPermission("cplot.bypass.deny")) {
                        if ($plotTo->isPlotDenied($player) && $plotTo->isOnPlot($player->getPosition())) {
                            $plotTo->teleportTo($player, false, false);
                            return;
                        }
                    }

                    // flags on plot enter
                    if ($plotFrom === null) {
                        // settings on plot enter
                        $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                        if ($playerData !== null) {
                            foreach ($plotTo->getFlags() as $flag) {
                                $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_WARN_FLAG . $flag->getID());
                                if (!($setting instanceof BooleanListAttribute)) {
                                    continue;
                                }
                                foreach ($setting->getValue() as $value) {
                                    if ($value === $flag->getValue()) {
                                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                            $player,
                                            ["prefix", "player.move.setting.warn_flag" => [$flag->getID(), $flag->toString()]]
                                        );
                                        continue 2;
                                    }
                                }
                            }
                        }

                        // title flag && message flag
                        $title = "";
                        /** @var BooleanAttribute $flag */
                        $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_TITLE);
                        if ($flag->getValue() === true) {
                            $title .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                                $player,
                                ["player.move.plotEnter.title.coordinates" => [$plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]]
                            );
                            if ($plotTo->hasPlotOwner()) {
                                $plotOwners = [];
                                foreach ($plotTo->getPlotOwners() as $plotOwner) {
                                    $plotOwnerData = $plotOwner->getPlayerData();
                                    $plotOwners[] = $plotOwnerData->getPlayerName() ?? "Error: " . ($plotOwnerData->getPlayerXUID() ?? $plotOwnerData->getPlayerUUID() ?? $plotOwnerData->getPlayerID());
                                }
                                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                                    $player,
                                    "player.move.plotEnter.title.owner.separator"
                                );
                                $list = implode($separator, $plotOwners);
                                $title .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                                    $player,
                                    ["player.move.plotEnter.title.owner" => $list]
                                );
                            }
                        }
                        /** @var StringAttribute $flag */
                        $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_MESSAGE);
                        if ($flag->getValue() !== "") {
                            $title .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                                $player,
                                ["player.move.plotEnter.title.flag.message" => $flag->getValue()]
                            );
                        }
                        $player->sendTip($title);

                        // plot_enter flag
                        /** @var BooleanAttribute $flag */
                        $flag = $plotTo->getFlagNonNullByID(FlagIDs::FLAG_PLOT_ENTER);
                        if ($flag->getValue() === true) {
                            foreach ($plotTo->getPlotOwners() as $plotOwner) {
                                $owner = $plotOwner->getPlayerData()->getPlayer();
                                if ($owner instanceof Player) {
                                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                        $owner,
                                        ["player.move.plotEnter.flag.plot_enter" => [$player->getName(), $plotTo->getWorldName(), $plotTo->getX(), $plotTo->getZ()]]
                                    );
                                }
                            }
                        }

                        // TODO: check_offlinetime flag && offline system
                    }
                }

                // plot leave
                if ($plotFrom instanceof Plot && $plotTo === null) {
                    // plot_leave flag
                    /** @var BooleanAttribute $flag */
                    $flag = $plotFrom->getFlagNonNullByID(FlagIDs::FLAG_PLOT_LEAVE);
                    if ($flag->getValue() === true) {
                        foreach ($plotFrom->getPlotOwners() as $plotOwner) {
                            $owner = $plotOwner->getPlayerData()->getPlayer();
                            if ($owner instanceof Player) {
                                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                    $owner,
                                    ["player.move.plotEnter.flag.plot_leave" => [$player->getName(), $plotFrom->getWorldName(), $plotFrom->getX(), $plotFrom->getZ()]]
                                );
                            }
                        }
                    }
                }
            }
        );
    }
}