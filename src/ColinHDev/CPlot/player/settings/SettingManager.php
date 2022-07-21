<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

use ColinHDev\CPlot\attributes\utils\AttributeParseException;
use ColinHDev\CPlot\player\settings\implementation\InformAddedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformDeniedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformRateAddSetting;
use ColinHDev\CPlot\player\settings\implementation\InformRemovedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformTrustedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformUndeniedSetting;
use ColinHDev\CPlot\player\settings\implementation\InformUntrustedSetting;
use ColinHDev\CPlot\ResourceManager;
use InvalidArgumentException;
use pocketmine\utils\SingletonTrait;
use function array_map;
use function gettype;
use function is_string;

final class SettingManager {
    use SingletonTrait;

    /**
     * @var Setting[]
     * @phpstan-var array<string, Setting<mixed>>
     */
    private array $settings = [];

    public function __construct() {
        $this->register($this->getSettingFromConfig(InformAddedSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformDeniedSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformRateAddSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformRemovedSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformTrustedSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformUndeniedSetting::TRUE()));
        $this->register($this->getSettingFromConfig(InformUntrustedSetting::TRUE()));
        /*$this->register(SettingIDs::SETTING_INFORM_TRUSTED_ADD, BooleanAttribute::class);
        $this->register(SettingIDs::SETTING_INFORM_TRUSTED_REMOVE, BooleanAttribute::class);

        $this->register(SettingIDs::SETTING_INFORM_HELPER_ADD, BooleanAttribute::class);
        $this->register(SettingIDs::SETTING_INFORM_HELPER_REMOVE, BooleanAttribute::class);

        $this->register(SettingIDs::SETTING_INFORM_DENIED_ADD, BooleanAttribute::class);
        $this->register(SettingIDs::SETTING_INFORM_DENIED_REMOVE, BooleanAttribute::class);

        $this->register(SettingIDs::SETTING_INFORM_PLOT_INACTIVE, BooleanAttribute::class);

        $this->register(SettingIDs::SETTING_INFORM_PLOT_RATE_ADD, BooleanAttribute::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_ITEM_DROP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_DROP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_DROP, BooleanListAttribute::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_ITEM_PICKUP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_PICKUP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP, BooleanListAttribute::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_PVP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_PVP, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PVP, BooleanListAttribute::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_PVE, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_PVE, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PVE, BooleanListAttribute::class);

        $this->register(SettingIDs::SETTING_WARN_FLAG_EXPLOSION, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_WARN_CHANGE_FLAG_EXPLOSION, BooleanListAttribute::class);
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION, BooleanListAttribute::class);*/
    }

    /**
     * @internal method to create a {@see Setting} instance with the default value defined in the config file.
     * @phpstan-template TValue of mixed
     * @phpstan-param Setting<TValue> $setting
     * @phpstan-return Setting<TValue>
     * @throws InvalidArgumentException if the given default value is not valid for the given setting.
     */
    private function getSettingFromConfig(Setting $setting) : Setting {
        $default = ResourceManager::getInstance()->getConfig()->getNested("setting." . $setting->getID());
        if ($default === null) {
            return $setting;
        }
        if (!is_string($default)) {
            throw new InvalidArgumentException(
                "Expected type of default value for setting " . $setting->getID() . " to be string, " . gettype($default) . " given in config file under \"setting." . $setting->getID() . "\"."
            );
        }
        try {
            $parsedValue = $setting->parse($default);
        } catch(AttributeParseException) {
            throw new InvalidArgumentException(
                "Failed to parse default value for setting " . $setting->getID() . ". Value \"" . $default . "\" given in config file under \"setting." . $setting->getID() . "\" was not accepted."
            );
        }
        return $setting->createInstance($parsedValue);
    }

    /**
     * Registers a {@see Setting} to the {@see SettingManager}.
     * @param Setting $setting The setting to register
     * @phpstan-template TValue of mixed
     * @phpstan-param Setting<TValue> $setting
     */
    public function register(Setting $setting) : void {
        $this->settings[$setting->getID()] = $setting;
    }

    /**
     * Returns all registered {@see Setting} instances with their value being the setting's default value.
     * @phpstan-return array<string, Setting<mixed>>
     */
    public function getSettings() : array {
        return array_map(
            static function(Setting $setting) : Setting {
                return clone $setting;
            },
            $this->settings
        );
    }

    /**
     * Returns the {@see Setting} with the given ID with its value being the its default value.
     * @phpstan-param string $ID
     * @phpstan-return ($ID is SettingIDs::* ? Setting<mixed> : null)
     */
    public function getSettingByID(string $ID) : ?Setting {
        if (!isset($this->settings[$ID])) {
            return null;
        }
        return clone $this->settings[$ID];
    }
}