<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\commands\AsyncSubcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\InternalSetting;
use ColinHDev\CPlot\player\settings\Setting;
use ColinHDev\CPlot\player\settings\SettingManager;
use ColinHDev\CPlot\provider\DataProvider;
use Generator;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function assert;
use function count;
use function implode;
use function is_array;

class SettingSubcommand extends AsyncSubcommand {

    public function executeAsync(CommandSender $sender, array $args) : Generator {
        if (count($args) === 0) {
            self::sendMessage($sender, ["prefix", "setting.usage"]);
            return;
        }

        switch ($args[0]) {
            case "list":
                $settingsByCategory = [];
                foreach (SettingManager::getInstance()->getSettings() as $setting) {
                    if ($setting instanceof InternalSetting) {
                        continue;
                    }
                    $settingCategory = self::translateForCommandSender($sender, "setting.category." . $setting->getID());
                    $settingsByCategory[$settingCategory][] = self::translateForCommandSender($sender, ["format.list.attribute" => $setting->getID()]);
                }
                $categories = [];
                $settingSeparator = self::translateForCommandSender($sender, "format.list.attribute.separator");
                foreach ($settingsByCategory as $category => $settings) {
                    $categories[] = self::translateForCommandSender($sender, [
                        "format.list.category" => [$category, implode($settingSeparator, $settings)]
                    ]);
                }
                $settingCategorySeparator = self::translateForCommandSender($sender, "format.list.category.separator");
                self::sendMessage($sender, ["prefix", "setting.list.success" => implode($settingCategorySeparator, $categories)]);
                break;

            case "info":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "setting.info.usage"]);
                    break;
                }
                $setting = SettingManager::getInstance()->getSettingByID($args[1]);
                if (!($setting instanceof Setting) || $setting instanceof InternalSetting) {
                    self::sendMessage($sender, ["prefix", "setting.info.settingNotFound" => $args[1]]);
                    break;
                }
                self::sendMessage($sender, [
                    "prefix",
                    "setting.info.success" => [
                        $setting->getID(),
                        self::translateForCommandSender($sender, "setting.category." . $setting->getID()),
                        self::translateForCommandSender($sender, "setting.description." . $setting->getID()),
                        self::translateForCommandSender($sender, "setting.type." . $setting->getID()),
                        $setting->getExample(),
                        $setting->toReadableString()
                    ]
                ]);
                break;

            case "my":
                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "setting.my.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "setting.my.loadError"]);
                    break;
                }
                $settings = [];
                foreach($playerData->getSettings() as $settingID => $setting) {
                    if (!$setting instanceof InternalSetting) {
                        $settings[] = self::translateForCommandSender($sender, ["format.list.attributeWithValue" => [$settingID, $setting->toReadableString()]]);
                    }
                }
                if (count($settings) === 0) {
                    self::sendMessage($sender, ["prefix", "setting.here.noSettings"]);
                    break;
                }
                self::sendMessage($sender, [
                    "prefix",
                    "setting.here.success" => implode(self::translateForCommandSender($sender, "format.list.attributeWithValue.separator"), $settings)
                ]);
                break;

            case "set":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "setting.set.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "setting.set.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "setting.set.loadError"]);
                    break;
                }

                $setting = SettingManager::getInstance()->getSettingByID($args[1]);
                if (!($setting instanceof Setting) || $setting instanceof InternalSetting) {
                    self::sendMessage($sender, ["prefix", "setting.set.settingNotFound" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.setting." . $setting->getID())) {
                    self::sendMessage($sender, ["prefix", "setting.set.permissionMessageForSetting" => $setting->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                $arg = implode(" ", $args);
                try {
                    $parsedValue = $setting->parse($arg);
                } catch (AttributeParseException) {
                    self::sendMessage($sender, ["prefix", "setting.set.parseError" => [$setting->getID(), $arg]]);
                    break;
                }

                $setting = $newSetting = $setting->createInstance($parsedValue);
                $oldSetting = $playerData->getLocalSettingByID($setting->getID());
                if ($oldSetting !== null) {
                    $setting = $oldSetting->merge($setting->getValue());
                }
                $playerData->addSetting($setting);
                yield DataProvider::getInstance()->savePlayerSetting($playerData, $setting);
                self::sendMessage($sender, ["prefix", "setting.set.success" => [$setting->getID(), $newSetting->toReadableString()]]);
                break;

            case "remove":
                if (!isset($args[1])) {
                    self::sendMessage($sender, ["prefix", "setting.remove.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    self::sendMessage($sender, ["prefix", "setting.remove.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    self::sendMessage($sender, ["prefix", "setting.remove.loadError"]);
                    break;
                }

                $setting = $playerData->getLocalSettingByID($args[1]);
                if (!($setting instanceof Setting) || $setting instanceof InternalSetting) {
                    self::sendMessage($sender, ["prefix", "setting.remove.settingNotSet" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.setting." . $setting->getID())) {
                    self::sendMessage($sender, ["prefix", "setting.remove.permissionMessageForSetting" => $setting->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && is_array($setting->getValue())) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $setting->parse($arg);
                        assert(is_array($parsedValues));
                    } catch (AttributeParseException) {
                        self::sendMessage($sender, ["prefix", "setting.remove.parseError" => [$setting->getID(), $arg]]);
                        break;
                    }

                    $values = $setting->getValue();
                    foreach ($values as $key => $value) {
                        $valueString = $setting->createInstance([$value])->toString();
                        foreach ($parsedValues as $parsedValue) {
                            if ($valueString === $setting->createInstance([$parsedValue])->toString()) {
                                unset($values[$key]);
                                continue 2;
                            }
                        }
                    }

                    if (count($values) > 0) {
                        $setting = $setting->createInstance($values);
                        $playerData->addSetting($setting);
                        yield DataProvider::getInstance()->savePlayerSetting($playerData, $setting);
                        self::sendMessage($sender, ["prefix", "setting.remove.success.value" => [$setting->getID(), $setting->toReadableString()]]);
                        break;
                    }
                }
                $playerData->removeSetting($setting->getID());
                yield DataProvider::getInstance()->deletePlayerSetting($playerData, $setting->getID());
                self::sendMessage($sender, ["prefix", "setting.remove.success.setting" => $setting->getID()]);
                break;

            default:
                self::sendMessage($sender, ["prefix", "setting.usage"]);
                break;
        }
    }
}