<?php

namespace ColinHDev\CPlotAPI\players;

use Closure;
use ColinHDev\CPlot\ResourceManager;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;

class SettingManager {

    use SingletonTrait;

    /** @var BaseSetting[] */
    private array $settings = [];

    public function __construct() {
        $config = ResourceManager::getInstance()->getSettingsConfig();

        $this->register($config, SettingIDs::SETTING_INFORM_TRUSTED_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_TRUSTED_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_HELPER_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_HELPER_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_DENIED_ADD, BooleanSetting::class);
        $this->register($config, SettingIDs::SETTING_INFORM_DENIED_REMOVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_PLOT_INACTIVE, BooleanSetting::class);

        $this->register($config, SettingIDs::SETTING_INFORM_PLOT_RATE_ADD, BooleanSetting::class);

        $parseBoolean = function (string $arg) : ?bool {
            return match ($arg) {
                "true" => true,
                "false" => false,
                default => null,
            };
        };
        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_TITLE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_TITLE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_TITLE, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_ENTER, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_PLOT_ENTER, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PLOT_ENTER, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_PLOT_LEAVE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_PLOT_LEAVE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PLOT_LEAVE, ArraySetting::class, $parseBoolean);*/

        /*$parseString = function (string $arg) : ?string {
            return $arg;
        };*/
        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_MESSAGE, ArraySetting::class, $parseString);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_MESSAGE, ArraySetting::class, $parseString);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_MESSAGE, ArraySetting::class, $parseString);*/

        /*$parsePosition = function (string $arg) : ?string {
            return null;
        };*/
        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_SPAWN, ArraySetting::class, $parsePosition);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_SPAWN, ArraySetting::class, $parsePosition);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_SPAWN, ArraySetting::class, $parsePosition);*/

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_DROP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_DROP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_DROP, ArraySetting::class, $parseBoolean);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_ITEM_PICKUP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_ITEM_PICKUP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_ITEM_PICKUP, ArraySetting::class, $parseBoolean);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_PVP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_PVP, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PVP, ArraySetting::class, $parseBoolean);

        $this->register($config, SettingIDs::SETTING_WARN_FLAG_EXPLOSION, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_EXPLOSION, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_EXPLOSION, ArraySetting::class, $parseBoolean);

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_BURNING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_BURNING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_BURNING, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_FLOWING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_FLOWING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_FLOWING, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_GROWING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_GROWING, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_GROWING, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_PLAYER_INTERACT, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_PLAYER_INTERACT, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PLAYER_INTERACT, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_SERVER_PLOT, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_SERVER_PLOT, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_SERVER_PLOT, ArraySetting::class, $parseBoolean);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_CHECK_INACTIVE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_CHECK_INACTIVE, ArraySetting::class, $parseBoolean);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_CHECK_INACTIVE, ArraySetting::class, $parseBoolean);*/

        /*$parseBlock = function (string $arg) : ?int {
            try {
                $block = LegacyStringToItemParser::getInstance()->parse($arg)->getBlock();
            } catch (LegacyStringToItemParserException $exception) {
                return null;
            }
            return $block->getFullId();
        };*/
        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_PLACE, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_PLACE, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_PLACE, ArraySetting::class, $parseBlock);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_BREAK, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_BREAK, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_BREAK, ArraySetting::class, $parseBlock);*/

        /*$this->register($config, SettingIDs::SETTING_WARN_FLAG_USE, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_WARN_CHANGE_FLAG_USE, ArraySetting::class, $parseBlock);
        $this->register($config, SettingIDs::SETTING_TELEPORT_CHANGE_FLAG_USE, ArraySetting::class, $parseBlock);*/
    }

    private function register(Config $config, string $ID, string $className, ?Closure $parseValue = null) : void {
        Utils::testValidInstance($className, BaseSetting::class);
        if ($parseValue === null) {
            $this->settings[$ID] = new $className($ID, $config->get($ID));
        } else {
            $this->settings[$ID] = new $className($ID, $config->get($ID), $parseValue);
        }
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