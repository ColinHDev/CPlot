<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\Settings;
use ColinHDev\CPlot\plots\flags\Flag;
use ColinHDev\CPlot\plots\flags\FlagManager;
use ColinHDev\CPlot\plots\flags\InternalFlag;
use ColinHDev\CPlot\plots\Plot;
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\worlds\WorldSettings;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function assert;
use function count;
use function implode;
use function is_array;

class FlagSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "flag.usage"]);
            return;
        }

        switch ($args[0]) {
            case "list":
                $flagsByCategory = [];
                foreach (FlagManager::getInstance()->getFlags() as $flag) {
                    if ($flag instanceof InternalFlag) {
                        continue;
                    }
                    $flagCategory = self::translateForCommandSender($sender, "flag.category." . $flag->getID());
                    $flagsByCategory[$flagCategory][] = self::translateForCommandSender($sender, ["format.list.flag" => $flag->getID()]);
                }
                $categories = [];
                $flagSeparator = self::translateForCommandSender($sender, "format.list.flag.separator");
                foreach ($flagsByCategory as $category => $flags) {
                    $categories[$category] = implode($flagSeparator, $flags);
                }
                $flagCategorySeparator = self::translateForCommandSender($sender, "format.list.flagCategory.separator");
                self::sendMessage($sender, ["prefix", "flag.list.success" => implode($flagCategorySeparator, $categories)]);
                break;

            case "info":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "flag.info.usage"]);
                    break;
                }
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if (!($flag instanceof Flag) || $flag instanceof InternalFlag) {
                    self::sendMessage($sender, ["prefix", "flag.info.flagNotFound" => $args[1]]);
                    break;
                }
                self::sendMessage($sender, [
                    "prefix", 
                    "flag.info.success" => [
                        $flag->getID(), 
                        self::translateForCommandSender($sender, "flag.category." . $flag->getID()),
                        self::translateForCommandSender($sender, "flag.description." . $flag->getID()),
                        self::translateForCommandSender($sender, "flag.type." . $flag->getID()),
                        $flag->getExample(),
                        $flag->toReadableString()
                    ]
                ]);
                break;

            case "here":
                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "flag.here.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    self::sendMessage($sender, ["prefix", "flag.here.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    self::sendMessage($sender, ["prefix", "flag.here.noPlot"]);
                    break;
                }
                $flags = [];
                foreach($plot->getFlags() as $flagID => $flag) {
                    if (!$flag instanceof InternalFlag) {
                        $flags[] = self::translateForCommandSender($sender, ["format.list.flagWithValue" => [$flagID, $flag->toReadableString()]]);
                    }
                }
                if (count($flags) === 0) {
                    self::sendMessage($sender, ["prefix", "flag.here.noFlags"]);
                    break;
                }
                self::sendMessage($sender, [
                    "prefix", 
                    "flag.here.success" => implode(self::translateForCommandSender($sender, "format.list.flagWithValue.separator"), $flags)
                ]);
                break;

            case "set":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "flag.set.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "flag.set.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    self::sendMessage($sender, ["prefix", "flag.set.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    self::sendMessage($sender, ["prefix", "flag.set.noPlot"]);
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    self::sendMessage($sender, ["prefix", "flag.set.noPlotOwner"]);
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender)) {
                        self::sendMessage($sender, ["prefix", "flag.set.notPlotOwner"]);
                        break;
                    }
                }

                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if (!($flag instanceof Flag) || $flag instanceof InternalFlag) {
                    self::sendMessage($sender, ["prefix", "flag.set.noFlag" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.flag." . $flag->getID())) {
                    self::sendMessage($sender, ["prefix", "flag.set.permissionMessageForFlag" => $flag->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                $arg = implode(" ", $args);
                try {
                    $parsedValue = $flag->parse($arg);
                } catch (AttributeParseException) {
                    self::sendMessage($sender, ["prefix", "flag.set.parseError" => [$arg, $flag->getID()]]);
                    break;
                }

                $flag = $newFlag = $flag->createInstance($parsedValue);
                $oldFlag = $plot->getLocalFlagByID($flag->getID());
                if ($oldFlag !== null) {
                    $flag = $oldFlag->merge($flag->getValue());
                }
                $plot->addFlag($flag);

                yield DataProvider::getInstance()->savePlotFlag($plot, $flag);
                self::sendMessage($sender, ["prefix", "flag.set.success" => [$flag->getID(), $newFlag->toReadableString()]]);
                foreach ($sender->getWorld()->getPlayers() as $player) {
                    if ($sender === $player || !$plot->isOnPlot($player->getPosition())) {
                        continue;
                    }
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                    if (!($playerData instanceof PlayerData)) {
                        continue;
                    }

                    if ($playerData->getSetting(Settings::WARN_FLAG_CHANGE())->contains($newFlag)) {
                        self::sendMessage(
                            $player,
                            ["prefix", "flag.set.setting.warn_change_flag" => [$newFlag->getID(), $newFlag->toReadableString()]]
                        );
                    }
                    if ($playerData->getSetting(Settings::TELEPORT_FLAG_CHANGE())->contains($newFlag)) {
                        $plot->teleportTo($player, TeleportDestination::ROAD_EDGE);
                    }
                }
                break;

            case "remove":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "flag.remove.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "flag.remove.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    self::sendMessage($sender, ["prefix", "flag.remove.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    self::sendMessage($sender, ["prefix", "flag.remove.noPlot"]);
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    self::sendMessage($sender, ["prefix", "flag.remove.noPlotOwner"]);
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender)) {
                        self::sendMessage($sender, ["prefix", "flag.remove.notPlotOwner"]);
                        break;
                    }
                }

                $flag = $plot->getLocalFlagByID($args[1]);
                if (!($flag instanceof Flag) || $flag instanceof InternalFlag) {
                    self::sendMessage($sender, ["prefix", "flag.remove.flagNotSet" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.flag." . $flag->getID())) {
                    self::sendMessage($sender, ["prefix", "flag.remove.permissionMessageForFlag" => $flag->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && is_array($flag->getValue())) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $flag->parse($arg);
                        assert(is_array($parsedValues));
                    } catch (AttributeParseException) {
                        self::sendMessage($sender, ["prefix", "flag.remove.parseError" => [$arg, $flag->getID()]]);
                        break;
                    }

                    $values = $flag->getValue();
                    $removedValues = [];
                    foreach ($values as $key => $value) {
                        $valueString = $flag->createInstance([$value])->toString();
                        foreach ($parsedValues as $parsedValue) {
                            if ($valueString === $flag->createInstance([$parsedValue])->toString()) {
                                $removedValues[] = $value;
                                unset($values[$key]);
                                continue 2;
                            }
                        }
                    }

                    if (count($values) > 0) {
                        $flag = $flag->createInstance($values);
                        $plot->addFlag($flag);
                        yield DataProvider::getInstance()->savePlotFlag($plot, $flag);
                        self::sendMessage($sender, ["prefix", "flag.remove.value.success" => [$flag->getID(), $flag->createInstance($removedValues)->toReadableString()]]);
                        break;
                    }
                }

                $plot->removeFlag($flag->getID());
                yield DataProvider::getInstance()->deletePlotFlag($plot, $flag->getID());
                self::sendMessage($sender, ["prefix", "flag.remove.flag.success" => $flag->getID()]);
                break;

            default:
                self::sendMessage($sender, ["prefix", "flag.usage"]);
                break;
        }
    }
}