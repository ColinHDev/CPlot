<?php

declare(strict_types=1);

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
use ColinHDev\CPlot\plots\TeleportDestination;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use ColinHDev\CPlot\worlds\WorldSettings;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class FlagSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.usage"]);
            return null;
        }

        switch ($args[0]) {
            case "list":
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.list.success"]);
                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $sender,
                    "flag.list.success.separator"
                );
                $flagsByCategory = [];
                foreach (FlagManager::getInstance()->getFlags() as $flag) {
                    $flagCategory = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                        $sender,
                        "flag.category." . $flag->getID()
                    );
                    if (!isset($flagsByCategory[$flagCategory])) {
                        $flagsByCategory[$flagCategory] = $flag->getID();
                    } else {
                        $flagsByCategory[$flagCategory] .= $separator . $flag->getID();
                    }
                }
                foreach ($flagsByCategory as $category => $flags) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.list.success.format" => [$category, $flags]]);
                }
                break;

            case "info":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.info.usage"]);
                    break;
                }
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.info.noFlag" => $args[1]]);
                    break;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.info.flag" => $flag->getID()]);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.info.ID" => $flag->getID()]);
                /** @phpstan-var string $category */
                $category = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "flag.category." . $flag->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.info.category" => $category]);
                /** @phpstan-var string $description */
                $description = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "flag.description." . $flag->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.info.description" => $description]);
                /** @phpstan-var string $type */
                $type = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "flag.type." . $flag->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.info.type" => $type]);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["flag.info.default" => $flag->getDefault()]);
                break;

            case "here":
                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.here.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.here.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.here.noPlot"]);
                    break;
                }
                $flags = $plot->getFlags();
                if (count($flags) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.here.noFlags"]);
                    break;
                }
                $flagStrings = [];
                foreach ($flags as $ID => $flag) {
                    $flagStrings[] = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                        $sender,
                        ["flag.here.success.format" => [$ID, $flag->toString()]]
                    );
                }
                /** @phpstan-var string $separator */
                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "flag.here.success.separator");
                $list = implode($separator, $flagStrings);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                    $sender,
                    ["prefix", "flag.here.success" => $list]
                );
                break;

            case "set":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.noPlot"]);
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.noPlotOwner"]);
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender)) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.notPlotOwner"]);
                        break;
                    }
                }

                /** @var BaseAttribute<mixed> | null $flag */
                $flag = FlagManager::getInstance()->getFlagByID($args[1]);
                if ($flag === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.noFlag" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.permissionMessageForFlag" => $flag->getID()]);
                    break;
                }

                if ($flag->getID() !== FlagIDs::FLAG_SERVER_PLOT) {
                    /** @var BooleanAttribute $serverPlotFlag */
                    $serverPlotFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($serverPlotFlag->getValue() === true) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.serverPlotFlag" => $serverPlotFlag->getID()]);
                        break;
                    }
                }

                if ($flag->getID() === FlagIDs::FLAG_SPAWN) {
                    $location = $sender->getLocation();
                    /** @var LocationAttribute $flag */
                    $arg = $flag->toString(
                        Location::fromObject(
                            $location->subtractVector($plot->getVector3()),
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
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.parseError" => [$arg, $flag->getID()]]);
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

                yield DataProvider::getInstance()->savePlotFlag($plot, $flag);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.set.success" => [$flag->getID(), $flag->toString($parsedValue)]]);
                foreach ($sender->getWorld()->getPlayers() as $player) {
                    if ($sender === $player) {
                        continue;
                    }
                    $plotOfPlayer = yield Plot::awaitFromPosition($player->getPosition());
                    if (!($plotOfPlayer instanceof Plot) || !$plotOfPlayer->isSame($plot)) {
                        continue;
                    }
                    $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($player);
                    if (!($playerData instanceof PlayerData)) {
                        continue;
                    }
                    /** @var ArrayAttribute<array<mixed, mixed>> $setting */
                    $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_WARN_CHANGE_FLAG . $newFlag->getID());
                    foreach ($setting->getValue() as $value) {
                        if ($value === $newFlag->getValue()) {
                            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                $player,
                                ["prefix", "flag.set.setting.warn_change_flag" => [$newFlag->getID(), $newFlag->toString()]]
                            );
                            break;
                        }
                    }

                    /** @var ArrayAttribute<array<mixed, mixed>> $setting */
                    $setting = $playerData->getSettingNonNullByID(SettingIDs::BASE_SETTING_TELEPORT_CHANGE_FLAG . $newFlag->getID());
                    foreach ($setting->getValue() as $value) {
                        if ($value === $newFlag->getValue()) {
                            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                                $player,
                                ["prefix", "flag.set.setting.teleport_change_flag" => [$newFlag->getID(), $newFlag->toString()]]
                            );
                            $plot->teleportTo($player, TeleportDestination::ROAD_EDGE);
                            break;
                        }
                    }
                }
                break;

            case "remove":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.senderNotOnline"]);
                    break;
                }
                if (!((yield DataProvider::getInstance()->awaitWorld($sender->getWorld()->getFolderName())) instanceof WorldSettings)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.noPlotWorld"]);
                    break;
                }
                $plot = yield Plot::awaitFromPosition($sender->getPosition());
                if (!($plot instanceof Plot)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.noPlot"]);
                    break;
                }

                if (!$plot->hasPlotOwner()) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.noPlotOwner"]);
                    break;
                }
                if (!$sender->hasPermission("cplot.admin.flag")) {
                    if (!$plot->isPlotOwner($sender)) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.notPlotOwner"]);
                        break;
                    }
                }

                /** @var BaseAttribute<mixed> | null $flag */
                $flag = $plot->getFlagByID($args[1]);
                if ($flag === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.flagNotSet" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission($flag->getPermission())) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.permissionMessageForFlag" => $flag->getID()]);
                    break;
                }

                if ($flag->getID() !== FlagIDs::FLAG_SERVER_PLOT) {
                    /** @var BooleanAttribute $serverPlotFlag */
                    $serverPlotFlag = $plot->getFlagNonNullByID(FlagIDs::FLAG_SERVER_PLOT);
                    if ($serverPlotFlag->getValue() === true) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.serverPlotFlag" => $serverPlotFlag->getID()]);
                        break;
                    }
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && $flag instanceof ArrayAttribute) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $flag->parse($arg);
                    } catch (AttributeParseException) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.parseError" => [$arg, $flag->getID()]]);
                        break;
                    }

                    $values = $flag->getValue();
                    assert(is_array($values));
                    $removedValues = [];
                    foreach ($values as $key => $value) {
                        $valueString = $flag->toString([$value]);
                        foreach ($parsedValues as $parsedValue) {
                            if ($valueString === $flag->toString([$parsedValue])) {
                                $removedValues[] = $value;
                                unset($values[$key]);
                                continue 2;
                            }
                        }
                    }

                    if (count($values) > 0) {
                        $flag = $flag->newInstance($values);
                        $plot->addFlag($flag);
                        yield DataProvider::getInstance()->savePlotFlag($plot, $flag);
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.value.success" => [$flag->getID(), $flag->toString($removedValues)]]);
                        break;
                    }
                }

                $plot->removeFlag($flag->getID());
                yield DataProvider::getInstance()->deletePlotFlag($plot, $flag->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.remove.flag.success" => $flag->getID()]);
                break;

            default:
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "flag.usage"]);
                break;
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "flag.saveError" => $error->getMessage()]);
    }
}