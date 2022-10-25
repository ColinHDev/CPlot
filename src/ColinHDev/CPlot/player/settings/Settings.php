<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

use ColinHDev\CPlot\player\settings\implementation\InformAddedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformDeniedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformRateAddSetting;
use ColinHDev\CPlot\player\settings\implementation\InformRemovedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformTrustedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformUndeniedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformUntrustedSetting;
use ColinHDev\CPlot\player\settings\implementation\TeleportFlagChangeSetting;
use ColinHDev\CPlot\player\settings\implementation\WarnFlagChangeSetting;
use ColinHDev\CPlot\player\settings\implementation\WarnFlagSetting;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static InformAddedSetting INFORM_ADDED()
 * @method static InformDeniedSetting INFORM_DENIED()
 * @method static InformRateAddSetting INFORM_RATE_ADD()
 * @method static InformRemovedSetting INFORM_REMOVED()
 * @method static InformTrustedSetting INFORM_TRUSTED()
 * @method static InformUndeniedSetting INFORM_UNDENIED()
 * @method static InformUntrustedSetting INFORM_UNTRUSTED()
 * @method static TeleportFlagChangeSetting TELEPORT_FLAG_CHANGE()
 * @method static WarnFlagChangeSetting WARN_FLAG_CHANGE()
 * @method static WarnFlagSetting WARN_FLAG()
 */
final class Settings {
    use CloningRegistryTrait;

    private function __construct() {
    }

    /**
     * @phpstan-param Setting<mixed> $setting
     */
    protected static function register(string $settingID, Setting $setting) : void{
        self::_registryRegister($settingID, $setting);
    }

    protected static function setup() : void {
        $settingManager = SettingManager::getInstance();
        self::register(SettingIDs::SETTING_INFORM_ADDED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_ADDED));
        self::register(SettingIDs::SETTING_INFORM_DENIED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_DENIED));
        self::register(SettingIDs::SETTING_INFORM_RATE_ADD, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_RATE_ADD));
        self::register(SettingIDs::SETTING_INFORM_REMOVED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_REMOVED));
        self::register(SettingIDs::SETTING_INFORM_TRUSTED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_TRUSTED));
        self::register(SettingIDs::SETTING_INFORM_UNDENIED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_UNDENIED));
        self::register(SettingIDs::SETTING_INFORM_UNTRUSTED, $settingManager->getSettingByID(SettingIDs::SETTING_INFORM_UNTRUSTED));
        self::register(SettingIDs::SETTING_TELEPORT_FLAG_CHANGE, $settingManager->getSettingByID(SettingIDs::SETTING_TELEPORT_FLAG_CHANGE));
        self::register(SettingIDs::SETTING_WARN_FLAG_CHANGE, $settingManager->getSettingByID(SettingIDs::SETTING_WARN_FLAG_CHANGE));
        self::register(SettingIDs::SETTING_WARN_FLAG, $settingManager->getSettingByID(SettingIDs::SETTING_WARN_FLAG));
    }
}