<?php

namespace ColinHDev\CPlotAPI\players;

use ColinHDev\CPlot\ResourceManager;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class SettingManager {

    use SingletonTrait;

    /** @var BaseSetting[] */
    private array $settings = [];

    public function __construct() {
        $config = ResourceManager::getInstance()->getFlagsConfig();

        $this->register($config, SettingIDs::SETTING_INFORM_TRUSTED_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_TRUSTED_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_HELPER_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_HELPER_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_DENIED_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_DENIED_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_PLOT_INACTIVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_PLOT_RATE_ADD, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_TITLE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_TITLE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_TITLE_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_ENTER, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_ENTER_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_PLOT_ENTER_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_LEAVE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_LEAVE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_PLOT_LEAVE_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_MESSAGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_MESSAGE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_MESSAGE_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_SPAWN, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_SPAWN_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_SPAWN_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_DROP, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_DROP_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_ITEM_DROP_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_PICKUP, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_PICKUP_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_ITEM_PICKUP_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PVP, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PVP_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_PVP_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_EXPLOSION, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_EXPLOSION_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_EXPLOSION_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_BURNING, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_BURNING_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_BURNING_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_FLOWING, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_FLOWING_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_FLOWING_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_GROWING, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_GROWING_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_GROWING_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLAYER_INTERACT, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLAYER_INTERACT_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_PLAYER_INTERACT_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_SERVER_PLOT, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_SERVER_PLOT_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_SERVER_PLOT_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_CHECK_INACTIVE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_CHECK_INACTIVE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_CHECK_INACTIVE_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLACE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PLACE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_PLACE_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_BREAK, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_BREAK_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_BREAK_CHANGE, ArraySetting::class);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_USE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_WARN_FLAG_USE_CHANGE, ArraySetting::class);
        $this->register($config, SettingIDs::SETTING_TELEPORT_FLAG_USE_CHANGE, ArraySetting::class);
    }

    private function register(Config $config, string $ID, string $className) : void {
        Utils::testValidInstance($className, BaseSetting::class);
        $this->settings[$ID] = new $className($ID, $config->get($ID));
    }

    /**
     * @return BaseSetting[]
     */
    public function getSettings() : array {
        return $this->settings;
    }

    public function getSettingByID(string $ID) : ?BaseSetting {
        if (!isset($this->settings[$ID])) return null;
        return clone $this->settings[$ID];
    }
}