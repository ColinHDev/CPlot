<?php

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlotAPI\attributes\ArrayAttribute;
use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use ColinHDev\CPlotAPI\attributes\utils\AttributeParseException;
use ColinHDev\CPlotAPI\players\settings\SettingIDs;
use ColinHDev\CPlotAPI\players\utils\PlayerDataException;
use ColinHDev\CPlotAPI\plots\flags\Flag;
use ColinHDev\CPlotAPI\plots\flags\FlagIDs;
use ColinHDev\CPlotAPI\plots\flags\FlagManager;
use ColinHDev\CPlotAPI\plots\flags\ServerPlotFlag;
use ColinHDev\CPlotAPI\plots\flags\SpawnFlag;
use ColinHDev\CPlotAPI\plots\Plot;
use ColinHDev\CPlotAPI\plots\utils\PlotException;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

class FlagSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : void {
        if (count($args) === 0) {
            $sender->sendMessage($this->getPrefix() . $this->getUsage());
            return;
        }

        switch ($args[0]) {
            case "list":
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.list.success"));
                $flagsByCategory = [];
                /** @var class-string<Flag> $flagClass */
                foreach (FlagManager::getInstance()->getFlags() as $flagClass) {
                    $flag = new $flagClass();
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
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noPlot"));
                    break;
                }
                try {
                    $flags = $plot->getFlags();
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.loadFlagsError"));
                    break;
                }
                if (count($flags) === 0) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.here.noFlags"));
                    break;
                }
                $flags = array_map(
                    function (Flag $flag) : string {
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
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlot"));
                    break;
                }

                try {
                    if (!$plot->hasPlotOwner()) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noPlotOwner"));
                        break;
                    }
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.loadPlotPlayersError"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.notPlotOwner"));
                        break;
                    }
                }

                try {
                    $plot->loadFlags();
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.loadFlagsError"));
                    break;
                }

                /** @var Flag&BaseAttribute | null $flag */
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.noFlag", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.permissionMessageForFlag", [$flag->getID()]));
                    break;
                }

                if (!$flag instanceof ServerPlotFlag) {
                    $oldFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($oldFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.serverPlotFlag", [$oldFlag->getID()]));
                        break;
                    }
                }

                if ($flag instanceof SpawnFlag) {
                    $location = $sender->getLocation();
                    $arg = $flag->toString(
                        Location::fromObject(
                            $location->subtractVector($plot->getPosition()),
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

                if (!$this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.saveError"));
                    break;
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.set.success", [$flag->getID(), $flag->toString($parsedValue)]));
                foreach ($sender->getWorld()->getPlayers() as $player) {
                    $playerUUID = $player->getUniqueId()->toString();
                    if ($sender->getUniqueId()->toString() === $playerUUID) {
                        continue;
                    }
                    $plotOfPlayer = Plot::fromPosition($player->getPosition());
                    if ($plotOfPlayer === null || !$plotOfPlayer->isSame($plot)) {
                        continue;
                    }
                    $playerData = $this->getPlugin()->getProvider()->getPlayerDataByUUID($playerUUID);
                    if ($playerData === null) {
                        continue;
                    }
                    try {
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
                    } catch (PlayerDataException | PlotException) {
                        continue;
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
                if ($this->getPlugin()->getProvider()->getWorld($sender->getWorld()->getFolderName()) === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotWorld"));
                    break;
                }
                $plot = Plot::fromPosition($sender->getPosition());
                if ($plot === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlot"));
                    break;
                }

                try {
                    if (!$plot->hasPlotOwner()) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.noPlotOwner"));
                        break;
                    }
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.loadPlotPlayersError"));
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender->getUniqueId()->toString())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.notPlotOwner"));
                        break;
                    }
                }

                try {
                    $plot->loadFlags();
                } catch (PlotException) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.loadFlagsError"));
                    break;
                }

                /** @var Flag | null $flag */
                $flag = $plot->getFlagByID($args[1]);
                if ($flag === null) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flagNotSet", [$args[1]]));
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.permissionMessageForFlag", [$flag->getID()]));
                    break;
                }

                if (!$flag instanceof ServerPlotFlag) {
                    $oldFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($oldFlag->getValue() === true) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.serverPlotFlag", [$oldFlag->getID()]));
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
                        if ($this->getPlugin()->getProvider()->savePlotFlag($plot, $flag)) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.value.success", [$flag->getID(), $flag->toString($removedValues)]));
                            break;
                        }
                    } else {
                        $plot->removeFlag($flag->getID());
                        if ($this->getPlugin()->getProvider()->deletePlotFlag($plot, $flag->getID())) {
                            $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flag.success", [$flag->getID()]));
                            break;
                        }
                    }

                } else {
                    $plot->removeFlag($flag->getID());
                    if ($this->getPlugin()->getProvider()->deletePlotFlag($plot, $flag->getID())) {
                        $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.flag.success", [$flag->getID()]));
                        break;
                    }
                }
                $sender->sendMessage($this->getPrefix() . $this->translateString("flag.remove.saveError"));
                break;

            default:
                $sender->sendMessage($this->getPrefix() . $this->getUsage());
                break;
        }
    }
}