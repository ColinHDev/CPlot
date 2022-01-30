<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\ArrayAttribute;
use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\attributes\LocationAttribute;
use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\SettingIDs;
use ColinHDev\CPlot\plots\flags\FlagIDs;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<void>
 */
class FlagSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }

        switch ($args[0]) {
            case "list":
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.list.success"));
                $flagsByCategory = [];
                foreach (FlagManager::getInstance()->getFlags() as $flag) {
                    $flagCategory = $this->translateString("flag.category." . $flag->getID());
                    if (!isset($flagsByCategory[$flagCategory])) {
                        $flagsByCategory[$flagCategory] = $flag->getID();
                    } else {
                        $flagsByCategory[$flagCategory] .= $this->translateString("flag.list.success.separator") . $flag->getID();
                    }
                }
                foreach ($flagsByCategory as $category => $flags) {
                    $sender->sendMessage($this->translateString("flag.list.success.format", [$category, $flags]));
                }
                break;

            case "info":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.usage"));
                    break;
                }
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.noFlag", [$args[1]]));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.info.flag", [$flag->getID()]));
                $sender->sendMessage($this->translateString("flag.info.ID", [$flag->getID()]));
                $sender->sendMessage($this->translateString("flag.info.category", [$this->translateString("flag.category." . $flag->getID())]));
                $sender->sendMessage($this->translateString("flag.info.description", [$this->translateString("flag.description." . $flag->getID())]));
                $sender->sendMessage($this->translateString("flag.info.type", [$this->translateString("flag.type." . $flag->getID())]));
                $sender->sendMessage($this->translateString("flag.info.default", [$flag->getDefault()]));
                break;

            case "here":
                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.senderNotOnline"));
                    break;
                }
                if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlotWorld"));
                    break;
                }
                $plot = yield from Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlot"));
                    break;
                }
                $flags = $plot->getFlags();
                if (count($flags) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noFlags"));
                    break;
                }
                $flags = array_map(
                    function (BaseAttribute $flag) : string {
                        return $this->translateString("flag.here.success.format", [$flag->getID(), $flag->toString()]);
                    },
                    $flags
                );
                $sender->sendMessage(
                    $this->getPrefix() .
                    $this->translateString(
                        "flag.here.success",
                        [implode($this->translateString("flag.here.success.separator"), $flags)]
                    )
                );
                break;

            case "set":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.senderNotOnline"));
                    break;
                }
                if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotWorld"));
                    break;
                }
                $plot = yield from Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlot"));
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotOwner"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.notPlotOwner"));
                        break;
                    }
                }

                /** @var BaseAttribute | null $flag */
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noFlag", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.permissionMessageForFlag", [$flag->getID()]));
                    break;
                }

                if ($flag->getID() !== FlagIDs::FLAG_SERVER_PLOT) {
                    /** @var BooleanAttribute $serverPlotFlag */
                    $serverPlotFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($serverPlotFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.serverPlotFlag", [$serverPlotFlag->getID()]));
                        break;
                    }
                }

                if ($flag->getID() === FlagIDs::FLAG_SPAWN) {
                    $location = $sender->getLocation();
                    /** @var LocationAttribute $flag */
                    $arg = $flag->toString(
                        Location::fromObject(
                            $location->subtractVector(yield from $plot->getVector3()),
                            $location->getWorld(),
                            $location->getYaw(),
                            $location->getPitch()
                        )
                    );
                } else {
                    array_splice($args, 0, 2);
                    $arg = implode(" ", $args);
                }
                try {
                    $parsedValue = $flag->parse($arg);
                } catch (AttributeParseException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.parseError", [$arg, $flag->getID()]));
                    break;
                }

                $flag = $newFlag = $flag->newInstance($parsedValue);
                $oldFlag = $plot->getFlagByID($flag->getID());
                if ($oldFlag !== null) {
                    $flag = $oldFlag->merge($flag->getValue());
                }
                $plot->addFlag(
                    $flag
                );

                yield from DataProvider::getInstance()->savePlotFlag($plot, $flag);
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.success", [$flag->getID(), $flag->toString($parsedValue)]));
                foreach ($sender->getWorld()->getPlayers() as $player) {
                    $playerUUID = $player->getUniqueId()->getBytes();
                    if ($sender->getUniqueId()->getBytes() === $playerUUID) {
                        continue;
                    }
                    $plotOfPlayer = yield from Plot::awaitFromPosition($player->getPosition());
                    if (!($plotOfPlayer instanceof Plot) || !$plotOfPlayer->isSame($plot)) {
                        continue;
                    }
                    $playerData = yield from DataProvider::getInstance()->awaitPlayerDataByUUID($playerUUID);
                    if (!($playerData instanceof PlayerData)) {
                        continue;
                    }
                    /** @var ArrayAttribute $setting */
                    $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_WARN_CHANGE_FLAG . $newFlag->getID());
                    if ($setting === null) {
                        continue;
                    }
                    foreach ($setting->getValue() as $value) {
                        if ($value === $newFlag->getValue()) {
                            $player->sendMessage(
                                $this->getPrefix() . $this->translateString("flag.set.setting.warn_change_flag", [$newFlag->getID(), $newFlag->toString()])
                            );
                            break;
                        }
                    }

                    /** @var ArrayAttribute $setting */
                    $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_TELEPORT_CHANGE_FLAG . $newFlag->getID());
                    if ($setting === null) {
                        continue;
                    }
                    foreach ($setting->getValue() as $value) {
                        if ($value === $newFlag->getValue()) {
                            $player->sendMessage(
                                $this->getPrefix() . $this->translateString("flag.set.setting.teleport_change_flag", [$newFlag->getID(), $newFlag->toString()])
                            );
                            $plot->teleportTo($player, false, false);
                            break;
                        }
                    }
                }
                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.usage"));
                    break;
                }

                if (!$sender instanceof Player) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.senderNotOnline"));
                    break;
                }
                if (!((yield from DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotWorld"));
                    break;
                }
                $plot = yield from Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlot"));
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotOwner"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender->getUniqueId()->getBytes())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.notPlotOwner"));
                        break;
                    }
                }

                /** @var BaseAttribute | null $flag */
                $flag = $plot->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flagNotSet", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.permissionMessageForFlag", [$flag->getID()]));
                    break;
                }

                if ($flag->getID() !== FlagIDs::FLAG_SERVER_PLOT) {
                    /** @var BooleanAttribute $serverPlotFlag */
                    $serverPlotFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($serverPlotFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.serverPlotFlag", [$serverPlotFlag->getID()]));
                        break;
                    }
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && $flag instanceof ArrayAttribute) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $flag->parse($arg);
                    } catch (AttributeParseException) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.parseError", [$arg, $flag->getID()]));
                        break;
                    }

                    $values = $flag->getValue();
                    $removedValues = [];
                    foreach ($parsedValues as $parsedValue) {
                        $parsedValueString = $flag->toString([$parsedValue]);
                        foreach ($flag->getValue() as $key => $value) {
                            if ($flag->toString([$value]) === $parsedValueString) {
                                $removedValues[] = $parsedValue;
                                unset($values[$key]);
                            }
                        }
                    }

                    if (count($values) > 0) {
                        $flag = $flag->newInstance($values);
                        $plot->addFlag($flag);
                        yield from DataProvider::getInstance()->savePlotFlag($plot, $flag);
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.value.success", [$flag->getID(), $flag->toString($removedValues)]));
                        break;
                    }
                }

                $plot->removeFlag($flag->getID());
                yield from DataProvider::getInstance()->deletePlotFlag($plot, $flag->getID());
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flag.success", [$flag->getID()]));
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                break;
        }
    }

    /**
     * @param \Throwable $error
     */
    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.saveError", [$error->getMessage()]));
    }
}