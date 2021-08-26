<?php

namespace ColinHDev\CPlot;

use pocketmine\utils\Config;
use pocketmine\lang\Language;
use pocketmine\utils\SingletonTrait;

class ResourceManager {

    use SingletonTrait;

    private Config $config;
    private Config $flagsConfig;
    private Language $language;
    private Config $settingsConfig;

    public function __construct() {
        if (!is_dir(CPlot::getInstance()->getDataFolder())) mkdir(CPlot::getInstance()->getDataFolder());
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) mkdir(CPlot::getInstance()->getDataFolder() . "schematics");

        CPlot::getInstance()->saveResource("config.yml");
        CPlot::getInstance()->saveResource("flags.yml");
        CPlot::getInstance()->saveResource("language.ini");
        CPlot::getInstance()->saveResource("settings.yml");

        $this->config = new Config(CPlot::getInstance()->getDataFolder() . "config.yml", Config::YAML);
        $this->flagsConfig = new Config(CPlot::getInstance()->getDataFolder() . "flags.yml", Config::YAML);
        $this->language = new Language("language", CPlot::getInstance()->getDataFolder(), "language");
        $this->settingsConfig = new Config(CPlot::getInstance()->getDataFolder() . "settings.yml", Config::YAML);
    }

    public function getPrefix() : string {
        return $this->language->get("prefix");
    }

    /**
     * @param string[]  $params
     */
    public function translateString(string $str, array $params = []) : string {
        return $this->language->translateString($str, $params);
    }

    public function getCommandData(string $commandName) : array {
        return [
            "name" => $this->language->get($commandName . ".name"),
            "alias" => json_decode($this->language->get($commandName . ".alias"), true),
            "description" => $this->language->get($commandName . ".description"),
            "usage" => $this->language->get($commandName . ".usage"),
            "permissionMessage" => $this->language->get($commandName . ".permissionMessage")
        ];
    }

    public function getConfig() : Config {
        $this->config->reload();
        return $this->config;
    }

    public function getFlagsConfig() : Config {
        $this->flagsConfig->reload();
        return $this->flagsConfig;
    }

    public function getSettingsConfig() : Config {
        $this->settingsConfig->reload();
        return $this->settingsConfig;
    }
}