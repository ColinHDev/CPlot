<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use Closure;
use ColinHDev\CPlot\attributes\BooleanListAttribute;
use ColinHDev\CPlot\event\PlayerEnteredPlotEvent;
use ColinHDev\CPlot\event\PlayerEnterPlotEvent;
use ColinHDev\CPlot\event\PlayerLeavePlotEvent;
use ColinHDev\CPlot\event\PlayerLeftPlotEvent;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\Flags;
use ColinHDev\CPlot\plots\flags\implementation\FarewellFlag;
use ColinHDev\CPlot\plots\flags\implementation\GreetingFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlotEnterFlag;
use ColinHDev\CPlot\plots\flags\implementation\PlotLeaveFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use function array_map;
use function implode;
use function strlen;

class PlayerMoveListener implements Listener {
    use APIHolder;

    /**
     * @handleCancelled false
     */
    public function onPlayerMove(PlayerMoveEvent $event) : void {
        $fromPlot = $this->getAPI()->getOrLoadPlotAtPosition($event->getFrom())->getResult();
        $toPlot = $this->getAPI()->getOrLoadPlotAtPosition($event->getTo())->getResult();
        if ($fromPlot === null || $toPlot === null) {
            Await::g2c(
                $this->onPlayerAsyncMove($event)
            );
            return;
        }

        $player = $event->getPlayer();

        if ($toPlot instanceof Plot) {
            if ($fromPlot === false) {
                $playerEnterPlotEvent = new PlayerEnterPlotEvent($toPlot, $player);
                if ($event->isCancelled()) {
                    $playerEnterPlotEvent->cancel();
                } else if (!$player->hasPermission("cplot.bypass.deny") && $toPlot->isPlotDenied($player)) {
                    $playerEnterPlotEvent->cancel();
                }
                $playerEnterPlotEvent->call();
                if ($playerEnterPlotEvent->isCancelled()) {
                    $event->cancel();
                    return;
                }
                $event->uncancel();
                $this->onPlotEnter($toPlot, $player);
            } else if (!$player->hasPermission("cplot.bypass.deny") && $toPlot->isPlotDenied($player)) {
                $toPlot->teleportTo($player, TeleportDestination::ROAD_EDGE);
                return;
            }
            return;
        }

        if ($fromPlot instanceof Plot) {
            $playerLeavePlotEvent = new PlayerLeavePlotEvent($fromPlot, $player);
            $playerLeavePlotEvent->call();
            if ($playerLeavePlotEvent->isCancelled()) {
                $event->cancel();
                return;
            }
            $event->uncancel();
            $this->onPlotLeave($fromPlot, $player);
        }
    }

    /**
     * This method is called when the plots a player left and entered, which are required to process a player's movement,
     * could not be synchronously loaded.
     * So we call this method to at least process the player's movement, although we no longer can cancel the
     * {@see PlayerMoveEvent} itself.
     * @phpstan-return \Generator<mixed, mixed, mixed, void>
     */
    private function onPlayerAsyncMove(PlayerMoveEvent $event) : \Generator {
        /** @var Plot|false $fromPlot */
        $fromPlot = yield from Await::promise(
            fn(Closure $resolve, Closure $reject) => $this->getAPI()->getOrLoadPlotAtPosition($event->getFrom())->onCompletion($resolve, $reject)
        );
        /** @var Plot|false $toPlot */
        $toPlot = yield from Await::promise(
            fn(Closure $resolve, Closure $reject) => $this->getAPI()->getOrLoadPlotAtPosition($event->getTo())->onCompletion($resolve, $reject)
        );

        $player = $event->getPlayer();
        if (!$player->isConnected()) {
            return;
        }

        if ($toPlot instanceof Plot) {
            $playerEnterPlotEvent = new PlayerEnteredPlotEvent($toPlot, $player);
            $playerEnterPlotEvent->call();
            if (!$player->hasPermission("cplot.bypass.deny") && $toPlot->isPlotDenied($player)) {
                $toPlot->teleportTo($player, TeleportDestination::ROAD_EDGE);
                return;
            }
            if ($fromPlot === false) {
                $this->onPlotEnter($toPlot, $player);
            }
            return;
        }

        if ($fromPlot instanceof Plot) {
            $playerLeavePlotEvent = new PlayerLeftPlotEvent($fromPlot, $player);
            $playerLeavePlotEvent->call();
            $this->onPlotLeave($fromPlot, $player);
        }
    }

    /**
     * This method is called when a player enters a plot.
     * @param Plot $plot The plot the player entered.
     * @param Player $player The player that entered the plot.
     */
    private function onPlotEnter(Plot $plot, Player $player) : void {
        Await::f2c(static function() use($plot, $player) : \Generator {
            // settings on plot enter
            $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
            if ($playerData !== null) {
                foreach ($plot->getFlags() as $flag) {
                    $setting = $playerData->getSettingByID(SettingIDs::BASE_SETTING_WARN_FLAG . $flag->getID());
                    if (!($setting instanceof BooleanListAttribute)) {
                        continue;
                    }
                    foreach ($setting->getValue() as $value) {
                        if ($value === $flag->getValue()) {
                            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                $player,
                                ["prefix", "playerMove.setting.warn_flag" => [$flag->getID(), $flag->toString()]]
                            );
                            continue 2;
                        }
                    }
                }
            }

            // tip && message flag
            $tip = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                $player,
                ["playerMove.plotEnter.tip.coordinates" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]
            );
            if ($plot->hasPlotOwner()) {
                $plotOwners = [];
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $plotOwnerData = $plotOwner->getPlayerData();
                    $plotOwners[] = $plotOwnerData->getPlayerName() ?? "Error: " . ($plotOwnerData->getPlayerXUID() ?? $plotOwnerData->getPlayerUUID() ?? $plotOwnerData->getPlayerID());
                }
                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    "playerMove.plotEnter.tip.owner.separator"
                );
                $list = implode($separator, $plotOwners);
                $tip .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    ["playerMove.plotEnter.tip.owner" => $list]
                );
            } else {
                $tip .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    "playerMove.plotEnter.tip.claimable"
                );
            }
            $flag = $plot->getFlag(Flags::GREETING());
            if (!$flag->equals(GreetingFlag::EMPTY())) {
                $tip .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    ["playerMove.plotEnter.tip.flag.greeting" => $flag->getValue()]
                );
            }
            $tipParts = explode(TextFormat::EOL, $tip);
            if (count($tipParts) > 1) {
                $longestPartLength = max(array_map(
                    static function(string $part) : int {
                        return strlen(TextFormat::clean($part));
                    },
                    $tipParts
                ));
                $tipParts = array_map(
                    static function(string $part) use($longestPartLength) : string {
                        $paddingSize = (int) floor(($longestPartLength - strlen(TextFormat::clean($part))) / 2);
                        if ($paddingSize <= 0) {
                            return $part;
                        }
                        return str_repeat(" ", $paddingSize) . $part;
                    },
                    $tipParts
                );
            }
            $player->sendTip(implode(TextFormat::EOL, $tipParts));

            // plot_enter flag
            if ($plot->getFlag(Flags::PLOT_ENTER())->equals(PlotEnterFlag::TRUE())) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner instanceof Player) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                            $owner,
                            ["playerMove.plotEnter.flag.plot_enter" => [$player->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                        );
                    }
                }
            }
        });
    }

    /**
     * This method is called when a player leaves a plot.
     * @param Plot $plot The plot the player left.
     * @param Player $player The player that left the plot.
     */
    public function onPlotLeave(Plot $plot, Player $player) : void {
        Await::f2c(static function() use($player, $plot) : \Generator {
            // farewell flag
            $flag = $plot->getFlag(Flags::FAREWELL());
            if (!$flag->equals(FarewellFlag::EMPTY())) {
                $tip = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    ["playerMove.plotLeave.tip.flag.farewell" => $flag->getValue()]
                );
                $player->sendTip($tip);
            }

            // plot_leave flag
            if ($plot->getFlag(Flags::PLOT_LEAVE())->equals(PlotLeaveFlag::TRUE())) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner instanceof Player) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                            $owner,
                            ["playerMove.plotLeave.flag.plot_leave" => [$player->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                        );
                    }
                }
            }
        });
    }
}