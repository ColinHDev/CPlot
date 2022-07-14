<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\commands\subcommands;

use ColinHDev\CPlot\attributes\ArrayAttribute;
use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\commands\Subcommand;
use ColinHDev\CPlot\player\PlayerData;
use ColinHDev\CPlot\player\settings\SettingManager;
use ColinHDev\CPlot\provider\DataProvider;
use ColinHDev\CPlot\provider\LanguageManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * @phpstan-extends Subcommand<mixed, mixed, mixed, null>
 */
class SettingSubcommand extends Subcommand {

    public function execute(CommandSender $sender, array $args) : \Generator {
        if (count($args) === 0) {
            yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.usage"]);
            return null;
        }

        switch ($args[0]) {
            case "list":
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.list.success"]);
                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                    $sender,
                    "flag.list.success.separator"
                );
                $settingsByCategory = [];
                foreach (SettingManager::getInstance()->getSettings() as $setting) {
                    $settingCategory = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                        $sender,
                        "setting.category." . $setting->getID()
                    );
                    if (!isset($settingsByCategory[$settingCategory])) {
                        $settingsByCategory[$settingCategory] = $setting->getID();
                    } else {
                        $settingsByCategory[$settingCategory] .= $separator . $setting->getID();
                    }
                }
                foreach ($settingsByCategory as $category => $settings) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.list.success.format" => [$category, $settings]]);
                }
                break;

            case "info":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.info.usage"]);
                    break;
                }
                $setting = SettingManager::getInstance()->getSettingByID($args[1]);
                if ($setting === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.info.noSetting" => $args[1]]);
                    break;
                }
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.info.setting" => $setting->getID()]);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.info.ID" => $setting->getID()]);
                /** @phpstan-var string $category */
                $category = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "setting.category." . $setting->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.info.category" => $category]);
                /** @phpstan-var string $description */
                $description = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "setting.description." . $setting->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.info.description" => $description]);
                /** @phpstan-var string $type */
                $type = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "setting.type." . $setting->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.info.type" => $type]);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["setting.info.default" => $setting->getDefault()]);
                break;

            case "my":
                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.my.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.my.loadPlayerDataError"]);
                    break;
                }
                $settings = $playerData->getSettings();
                if (count($settings) === 0) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.my.noSettings"]);
                    break;
                }
                $settingStrings = [];
                foreach ($settings as $ID => $setting) {
                    $settingStrings[] = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender(
                        $sender,
                        ["setting.my.success.format" => [$ID, $setting->toString()]]
                    );
                }
                /** @phpstan-var string $separator */
                $separator = yield from LanguageManager::getInstance()->getProvider()->awaitTranslationForCommandSender($sender, "setting.my.success.separator");
                $list = implode($separator, $settingStrings);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage(
                    $sender,
                    ["prefix", "setting.my.success" => $list]
                );
                break;

            case "set":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.loadPlayerDataError"]);
                    break;
                }

                /** @var BaseAttribute<mixed> | null $setting */
                $setting = SettingManager::getInstance()->getSettingByID($args[1]);
                if ($setting === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.noSetting" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.setting." . $setting->getID())) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.permissionMessageForSetting" => $setting->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                $arg = implode(" ", $args);
                try {
                    $parsedValue = $setting->parse($arg);
                } catch (AttributeParseException) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.parseError" => [$arg, $setting->getID()]]);
                    break;
                }

                $setting = $setting->newInstance($parsedValue);
                $oldSetting = $playerData->getSettingByID($setting->getID());
                if ($oldSetting !== null) {
                    $setting = $oldSetting->merge($setting->getValue());
                }
                $playerData->addSetting(
                    $setting
                );
                yield DataProvider::getInstance()->savePlayerSetting($playerData, $setting);
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.set.success" => [$setting->getID(), $setting->toString($parsedValue)]]);
                break;

            case "remove":
                if (!isset($args[1])) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.usage"]);
                    break;
                }

                if (!$sender instanceof Player) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.senderNotOnline"]);
                    break;
                }
                $playerData = yield DataProvider::getInstance()->awaitPlayerDataByPlayer($sender);
                if (!($playerData instanceof PlayerData)) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.loadPlayerDataError"]);
                    break;
                }

                /** @var BaseAttribute<mixed> | null $setting */
                $setting = $playerData->getSettingByID($args[1]);
                if ($setting === null) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.settingNotSet" => $args[1]]);
                    break;
                }
                if (!$sender->hasPermission("cplot.setting." . $setting->getID())) {
                    yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.permissionMessageForSetting" => $setting->getID()]);
                    break;
                }

                array_splice($args, 0, 2);
                if (count($args) > 0 && $setting instanceof ArrayAttribute) {
                    $arg = implode(" ", $args);
                    try {
                        $parsedValues = $setting->parse($arg);
                    } catch (AttributeParseException) {
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.parseError" => [$arg, $setting->getID()]]);
                        break;
                    }

                    $values = $setting->getValue();
                    assert(is_array($values));
                    foreach ($values as $key => $value) {
                        $valueString = $setting->toString([$value]);
                        foreach ($parsedValues as $parsedValue) {
                            if ($valueString === $setting->toString([$parsedValue])) {
                                unset($values[$key]);
                                continue 2;
                            }
                        }
                    }

                    if (count($values) > 0) {
                        $setting = $setting->newInstance($values);
                        $playerData->addSetting($setting);
                        yield DataProvider::getInstance()->savePlayerSetting($playerData, $setting);
                        yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.value.success" => [$setting->getID(), $setting->toString()]]);
                        break;
                    }
                }
                $playerData->removeSetting($setting->getID());
                yield DataProvider::getInstance()->deletePlayerSetting($playerData, $setting->getID());
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.remove.setting.success" => $setting->getID()]);
                break;

            default:
                yield from LanguageManager::getInstance()->getProvider()->awaitMessageSendage($sender, ["prefix", "setting.usage"]);
                break;
        }
        return null;
    }

    public function onError(CommandSender $sender, \Throwable $error) : void {
        if ($sender instanceof Player && !$sender->isConnected()) {
            return;
        }
        LanguageManager::getInstance()->getProvider()->sendMessage($sender, ["prefix", "setting.saveError" => $error->getMessage()]);
    }
}