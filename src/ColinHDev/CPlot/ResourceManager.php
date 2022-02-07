<?php

declare(strict_types=1);

namespace ColinHDev\CPlot;

use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class ResourceManager {
    use SingletonTrait;

    private Config $bordersConfig;
    private Config $config;
    private Language $language;
    private Config $wallsConfig;

    public function __construct() {
        if (!is_dir(CPlot::getInstance()->getDataFolder() . "schematics")) mkdir(CPlot::getInstance()->getDataFolder() . "schematics");

        CPlot::getInstance()->saveResource("borders.yml");
        CPlot::getInstance()->saveResource("config.yml");
        CPlot::getInstance()->saveResource("language.ini");
        CPlot::getInstance()->saveResource("walls.yml");

        $this->bordersConfig = new Config(CPlot::getInstance()->getDataFolder() . "borders.yml", Config::YAML);
        $this->config = new Config(CPlot::getInstance()->getDataFolder() . "config.yml", Config::YAML);
        $this->language = new Language("language", CPlot::getInstance()->getDataFolder(), "language");
        $this->wallsConfig = new Config(CPlot::getInstance()->getDataFolder() . "walls.yml", Config::YAML);
    }

    public function getPrefix() : string {
        return $this->language->get("prefix");
    }

    /**
     * @phpstan-param (float|int|string|Translatable)[] $params
     */
    public function translateString(string $str, array $params = []) : string {
        return $this->language->translateString($str, $params);
    }

    /**
     * @phpstan-return array{name: string, alias: array<string>, description: string, usage: string, permissionMessage: string}
     * @throws \JsonException
     */
    public function getCommandData(string $commandName) : array {
        $alias = json_decode($this->language->get($commandName . ".alias"), true, 512, JSON_THROW_ON_ERROR);
        assert(is_array($alias));
        /** @phpstan-var array<string> $alias */
        return [
            "name" => $this->language->get($commandName . ".name"),
            "alias" => $alias,
            "description" => $this->language->get($commandName . ".description"),
            "usage" => $this->language->get($commandName . ".usage"),
            "permissionMessage" => $this->language->get($commandName . ".permissionMessage")
        ];
    }

    public function getBordersConfig() : Config {
        $this->bordersConfig->reload();
        return $this->bordersConfig;
    }

    public function getConfig() : Config {
        $this->config->reload();
        return $this->config;
    }

    public function getWallsConfig() : Config {
        $this->wallsConfig->reload();
        return $this->wallsConfig;
    }
}