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
use ColinHDev\CPlot\player\settings\implementation\TeleportFlagChangeSetting;
use ColinHDev\CPlot\player\settings\implementation\WarnFlagChangeSetting;
use ColinHDev\CPlot\player\settings\implementation\WarnFlagSetting;
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
        $this->register($this->getSettingFromConfig(TeleportFlagChangeSetting::NONE()));
        $this->register($this->getSettingFromConfig(WarnFlagChangeSetting::NONE()));
        $this->register($this->getSettingFromConfig(WarnFlagSetting::NONE()));
    }

    /**
     * @internal method to create a {@see Setting} instance with the default value defined in the config file.
     * @template TValue of mixed
     * @param Setting<TValue> $setting
     * @return Setting<TValue>
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
     * @template TValue of mixed
     * @param Setting<TValue> $setting
     */
    public function register(Setting $setting) : void {
        $this->settings[$setting->getID()] = $setting;
    }

    /**
     * Returns all registered {@see Setting} instances with their value being the setting's default value.
     * @return array<string, Setting<mixed>>
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
     * @param string $ID
     * @phpstan-return ($ID is SettingIDs::* ? Setting<mixed> : null)
     */
    public function getSettingByID(string $ID) : ?Setting {
        if (!isset($this->settings[$ID])) {
            return null;
        }
        return clone $this->settings[$ID];
    }
}