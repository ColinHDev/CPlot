<?php

namespace ColinHDev\CPlot;

use pocketmine\utils\Config;
use pocketmine\lang\Language;

class ResourceManager {

    private static ResourceManager $instance;

    private Config $config;
    private Config $flagsConfig;
    private Language $language;

    public function __construct() {
        self::$instance = $this;

        if (!is_dir(CPlot::getInstance()->getDataFolder())) mkdir(CPlot::getInstance()->getDataFolder());
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) mkdir(CPlot::getInstance()->getDataFolder() . "schematics");

        CPlot::getInstance()->saveResource("config.yml");
        CPlot::getInstance()->saveResource("flags.yml");
        CPlot::getInstance()->saveResource("language.ini");

        $this->config = new Config(CPlot::getInstance()->getDataFolder() . "config.yml", Config::YAML);
        $this->language = new Language("language", CPlot::getInstance()->getDataFolder(), "language");
    }

    /**
     * @return ResourceManager
     */
    public static function getInstance() : ResourceManager {
        return self::$instance;
    }

    /**
     * @return string
     */
    public function getPrefix() : string {
        return $this->language->get("prefix");
    }

    /**
     * @param string            $str
     * @param array | string[]  $params
     * @return string
     */
    public function translateString(string $str, array $params = []) : string {
        return $this->language->translateString($str, $params);
    }

    /**
     * @param string $commandName
     * @return array
     */
    public function getCommandData(string $commandName) : array {
        return [
            "name" => $this->language->get($commandName . ".name"),
            "alias" => json_decode($this->language->get($commandName . ".alias"), true),
            "description" => $this->language->get($commandName . ".description"),
            "usage" => $this->language->get($commandName . ".usage"),
            "permissionMessage" => $this->language->get($commandName . ".permissionMessage")
        ];
    }

    /**
     * @return Config
     */
    public function getConfig() : Config {
        $this->config->reload();
        return $this->config;
    }

    /**
     * @return Config
     */
    public function getFlagsConfig() : Config {
        $this->flagsConfig->reload();
        return $this->flagsConfig;
    }
}