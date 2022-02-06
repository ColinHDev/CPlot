<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\player\settings;

use ColinHDev\CPlot\attributes\BaseAttribute;
use ColinHDev\CPlot\attributes\BooleanAttribute;
use ColinHDev\CPlot\attributes\BooleanListAttribute;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class SettingManager {
    use SingletonTrait;

    /** @var array<string, BaseAttribute<mixed>> */
    private array $settings = [];

    public function __construct() {
        $this->register(SettingIDs::SETTING_INFORM_TRUSTED_ADD, BooleanAttribute::class);
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
        $this->register(SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION, BooleanListAttribute::class);
    }

    /**
     * @phpstan-template TAttributeClass of BaseAttribute<mixed>
     * @phpstan-param class-string<TAttributeClass> $className
     * @throws \InvalidArgumentException
     */
    private function register(string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseAttribute::class);
        $this->settings[$ID] = new $className(
            $ID,
            "cplot.setting." . $ID,
            ResourceManager::getInstance()->getConfig()->getNested("setting." . $ID)
        );
    }

    /**
     * @return array<string, BaseAttribute<mixed>>
     */
    public function getSettings() : array {
        return $this->settings;
    }

    /**
     * @phpstan-return BaseAttribute<mixed>
     */
    public function getSettingByID(string $ID) : ?BaseAttribute {
        if (!isset($this->settings[$ID])) {
            return null;
        }
        return clone $this->settings[$ID];
    }
}