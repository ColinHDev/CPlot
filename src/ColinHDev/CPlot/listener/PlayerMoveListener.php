<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\listener;

use Closure;
use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\attributes\BooleanListAttribute;
use ColinHDev\CPlot\attributes\StringAttribute;
use ColinHDev\CPlot\event\PlayerEnteredPlotEvent;
use ColinHDev\CPlot\event\PlayerEnterPlotEvent;
use ColinHDev\CPlot\event\PlayerLeavePlotEvent;
use ColinHDev\CPlot\event\PlayerLeftPlotEvent;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\utils\APIHolder;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

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
            $flag = $plot->getFlagByID(FlagIDs::FLAG_TITLE);
            if ($flag->getValue() === true) {
                $title .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    ["player.move.plotEnter.title.coordinates" => [$plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                );
                if ($plot->hasPlotOwner()) {
                    $plotOwners = [];
                    foreach ($plot->getPlotOwners() as $plotOwner) {
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
            $flag = $plot->getFlagByID(FlagIDs::FLAG_MESSAGE);
            if ($flag->getValue() !== "") {
                $title .= yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $player,
                    ["player.move.plotEnter.title.flag.message" => $flag->getValue()]
                );
            }
            $player->sendTip($title);

            // plot_enter flag
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagByID(FlagIDs::FLAG_PLOT_ENTER);
            if ($flag->getValue() === true) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner instanceof Player) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                            $owner,
                            ["player.move.plotEnter.flag.plot_enter" => [$player->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
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
            // plot_leave flag
            /** @var BooleanAttribute $flag */
            $flag = $plot->getFlagByID(FlagIDs::FLAG_PLOT_LEAVE);
            if ($flag->getValue() === true) {
                foreach ($plot->getPlotOwners() as $plotOwner) {
                    $owner = $plotOwner->getPlayerData()->getPlayer();
                    if ($owner instanceof Player) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                            $owner,
                            ["player.move.plotEnter.flag.plot_leave" => [$player->getName(), $plot->getWorldName(), $plot->getX(), $plot->getZ()]]
                        );
                    }
                }
            }
        });
    }
}