<?php

namespace ColinHDev\CPlotAPI\players\settings;

use ColinHDev\CPlotAPI\attributes\BaseAttribute;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class SettingManager {
    use SingletonTrait;

    /** @var class-string<Setting>[] */
    private array $settings = [];

    public function __construct() {
        $this->register(SettingIDs::SETTING_INFORM_TRUSTED_ADD, InformTrustedAddSetting::class);
        $this->register(SettingIDs::SETTING_INFORM_TRUSTED_REMOVE, InformTrustedRemoveSetting::class);

        $this->register(SettingIDs::SETTING_INFORM_HELPER_ADD, InformHelperAddSetting::class);
        $this->register(SettingIDs::SETTING_INFORM_HELPER_REMOVE, InformHelperRemoveSetting::class);

        $this->register(SettingIDs::SETTING_INFORM_DENIED_ADD, InformDeniedAddSetting::class);
        $this->register(SettingIDs::SETTING_INFORM_DENIED_REMOVE, InformDeniedRemoveSetting::class);

        $this->register(SettingIDs::SETTING_INFORM_PLOT_INACTIVE, InformPlotInactiveSetting::class);

        $this->register(SettingIDs::SETTING_INFORM_PLOT_RATE_ADD, InformPlotRateAddSetting::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_ITEM_DROP, WarnItemDropFlagSetting::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_DROP, WarnItemDropFlagChangeSetting::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_DROP, TeleportItemDropFlagChangeSetting::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_ITEM_PICKUP, WarnItemPickupFlagSetting::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_PICKUP, WarnItemPickupFlagChangeSetting::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP, TeleportItemPickupFlagChangeSetting::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_PVP, WarnPvpFlagSetting::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_PVP, WarnPvpFlagChangeSetting::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PVP, TeleportPvpFlagChangeSetting::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_PVE, WarnPveFlagSetting::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_PVE, WarnPveFlagChangeSetting::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PVE, TeleportPveFlagChangeSetting::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_EXPLOSION, WarnExplosionFlagSetting::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_EXPLOSION, WarnExplosionFlagChangeSetting::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION, TeleportExplosionFlagChangeSetting::class);
    }

    /**
     * @param string $ID
     * @param class-string<Setting> $className
     */
    private function register(string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseAttribute::class);
        /** @var class-string<Setting> $className */
        $this->settings[$ID] = $className;
    }

    /**
     * @return class-string<Setting>[]
     */
    public function getSettings() : array {
        return $this->settings;
    }

    public function getSettingByID(string $ID) : ?Setting {
        if (!isset($this->settings[$ID])) {
            return null;
        }
        return new $this->settings[$ID]();
    }
}